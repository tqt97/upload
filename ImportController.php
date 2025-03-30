<?php
class Controller_Import extends Controller
{
    public function action_csv()
    {
        $data_array = [];
        $success_count = 0;

        if (Input::method() == 'POST' && Input::file('csv_file')) {
            $config = [
                'path' => DOCROOT . 'uploads',
                'ext_whitelist' => ['csv'],
            ];

            Upload::process($config);
            if (Upload::is_valid()) {
                Upload::save();
                $file = Upload::get_files(0);
                $file_path = $file['saved_to'] . $file['saved_as'];

                $handle = fopen($file_path, 'r');
                if ($handle) {
                    $headers = fgetcsv($handle, 1000, ',');
                    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                        $row_data = array_combine($headers, $data);
                        if ($row_data) {
                            $data_array[] = $row_data;
                        }
                    }
                    fclose($handle);
                    File::delete($file_path);
                } else {
                    Session::set_flash('error', 'Không thể mở file CSV!');
                    return Response::forge(View::forge('import/csv'));
                }
            } else {
                Session::set_flash('error', Upload::get_errors());
                return Response::forge(View::forge('import/csv'));
            }
        }

        if (!empty($data_array)) {
            $dataController = new Controller_Data();
            foreach ($data_array as $row) {
                if ($dataController->action_store($row)) {
                    $success_count++;
                }
            }
            Session::set_flash('success', "Đã import thành công $success_count bản ghi!");
        }

        return Response::forge(View::forge('import/csv'));
    }
}
