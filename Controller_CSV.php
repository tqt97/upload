<?php

class Controller_CSV extends Controller
{
    public function action_index()
    {
        return Response::forge(View::forge('csv/index'));
    }

    public function action_import()
    {
        if (Input::method() != 'POST' || !isset($_FILES['csv_file'])) {
            return Response::forge(json_encode(['status' => 'error', 'message' => 'Không thể upload file!']), 400);
        }

        $file = $_FILES['csv_file'];
        $filePath = APPPATH . 'tmp/' . uniqid() . '_' . basename($file['name']);

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return Response::forge(json_encode(['status' => 'error', 'message' => 'Lỗi lưu file!']), 500);
        }

        return Response::forge(json_encode(['status' => 'success', 'file' => $filePath]), 200);
    }

    public function action_process()
    {
        $filePath = Input::post('file');
        if (!file_exists($filePath)) {
            return Response::forge(json_encode(['status' => 'error', 'message' => 'File không tồn tại!']), 400);
        }

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            if (!$headers) {
                return Response::forge(json_encode(['status' => 'error', 'message' => 'File CSV không có tiêu đề!']), 400);
            }

            $errors = [];
            $importedCount = 0;
            $totalRows = count(file($filePath)) - 1; // Tổng số dòng (trừ header)
            $processed = 0;

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $processed++;
                $data = array_combine($headers, $row);
                if (!$data) continue;

                $name  = trim($data['name'] ?? '');
                $email = trim($data['email'] ?? '');
                $phone = trim($data['phone'] ?? '');

                if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{10,15}$/', $phone)) {
                    $errors[] = "Lỗi dòng {$processed}: dữ liệu không hợp lệ!";
                    continue;
                }

                if (Model_User::query()->where('email', $email)->count() > 0) {
                    $errors[] = "Lỗi: Email '{$email}' đã tồn tại!";
                    continue;
                }

                try {
                    $user = Model_User::forge([
                        'name'  => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]);
                    $user->save();
                    $importedCount++;
                } catch (Exception $e) {
                    $errors[] = "Lỗi khi lưu '{$email}': " . $e->getMessage();
                }

                echo json_encode(['progress' => ($processed / $totalRows) * 100]);
                ob_flush();
                flush();
            }
            fclose($handle);
            unlink($filePath);

            return Response::forge(json_encode([
                'status'  => 'success',
                'imported' => $importedCount,
                'errors'   => $errors
            ]), 200);
        }

        return Response::forge(json_encode(['status' => 'error', 'message' => 'Không thể mở file!']), 500);
    }
}
