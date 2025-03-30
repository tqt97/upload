<?php
class Controller_Import extends Controller
{
    public function action_import()
    {
        $data_array = [];
        $success_count = 0;

        if (\Input::method() == 'POST' && \Input::file('file_input')) {
            // Gọi ImportService để xử lý import
            $data_array = \Service\ImportService::importFile(\Input::file('file_input'));
        }

        // Gọi Controller_Data để thêm dữ liệu
        if (!empty($data_array)) {
            $dataController = new Controller_Data();
            foreach ($data_array as $row) {
                if ($dataController->action_store($row)) {
                    $success_count++;
                }
            }
            \Session::set_flash('success', "Đã import thành công $success_count bản ghi!");
        }

        return \Response::forge(\View::forge('import/import'));
    }
}
