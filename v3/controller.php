<?php
class Controller_Import extends Controller
{
    public function action_import()
    {
        $success_count = 0;

        if (\Input::method() == 'POST' && \Input::file('file_input')) {
            $use_generator = true; // Dùng generator nếu file lớn
            $data = \Helper\Import::importFile(\Input::file('file_input'), $use_generator);

            $dataController = new Controller_Data();
            if ($use_generator) {
                foreach ($data as $row) {
                    if ($dataController->action_store($row)) {
                        $success_count++;
                    }
                }
            } else {
                foreach ($data as $row) {
                    if ($dataController->action_store($row)) {
                        $success_count++;
                    }
                }
            }

            if ($success_count > 0) {
                \Session::set_flash('success', "Đã import thành công $success_count bản ghi!");
            }
        }

        return \Response::forge(\View::forge('import/import'));
    }
}
