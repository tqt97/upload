<?php
class Controller_Import extends Controller
{
    public function action_import()
    {
        $response = ['success' => false, 'message' => '', 'errors' => [], 'data' => []];

        if (\Input::method() == 'POST' && \Input::file('file_input')) {
            $use_generator = false; // Tạm dùng mảng, có thể đổi sang true nếu cần
            $data = \Helper\Import::importFile(\Input::file('file_input'), $use_generator);

            $dataController = new Controller_Data();
            $success_count = 0;

            foreach ($data as $row) {
                if ($dataController->action_store($row)) {
                    $success_count++;
                }
            }

            if ($success_count > 0) {
                $response['success'] = true;
                $response['message'] = "Đã import thành công $success_count bản ghi!";
            }

            // Lấy lỗi từ Session nếu có
            if ($errors = \Session::get_flash('error')) {
                $response['errors'] = (array) $errors;
            }
        } else {
            $response['errors'][] = 'Không tìm thấy file hoặc yêu cầu không hợp lệ!';
        }

        // Trả về JSON
        return \Response::forge(json_encode($response), 200, ['Content-Type' => 'application/json']);
    }
}
