<?php
namespace Helper;

class Import
{
    /**
     * Xử lý import file và trả về mảng dữ liệu hoặc generator
     * @param array $file Thông tin file từ Input::file()
     * @param bool $use_generator Sử dụng generator để xử lý dữ liệu lớn
     * @param array $required_columns Các cột bắt buộc (mặc định: name, email)
     * @return array|Generator Mảng dữ liệu hoặc generator
     */
    public static function importFile($file, $use_generator = false, $required_columns = ['name', 'email'])
    {
        $data_array = [];
        $errors = [];
        $config = [
            'path' => DOCROOT . 'uploads',
            'ext_whitelist' => ['csv'],
            'max_size' => 10240, // Giới hạn 10MB, tùy chỉnh nếu cần
        ];

        // Xử lý upload file
        \Upload::process($config);
        if (\Upload::is_valid()) {
            \Upload::save();
            $uploaded_file = \Upload::get_files(0);
            $file_path = $uploaded_file['saved_to'] . $uploaded_file['saved_as'];
            $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

            if ($file_ext === 'csv') {
                if ($use_generator) {
                    return static::readCSVGenerator($file_path, $required_columns); // Trả về generator cho dữ liệu lớn
                } else {
                    $data_array = static::readCSV($file_path, $required_columns, $errors);
                }
            } else {
                $errors[] = 'Định dạng file không được hỗ trợ! Chỉ hỗ trợ CSV.';
            }

            \File::delete($file_path);
        } else {
            $errors = array_merge($errors, \Upload::get_errors());
        }

        if (!empty($errors)) {
            \Session::set_flash('error', $errors);
        }

        return $data_array;
    }

    /**
     * Đọc file CSV và trả về mảng dữ liệu
     * @param string $file_path Đường dẫn file
     * @param array $required_columns Các cột bắt buộc
     * @param array &$errors Mảng lỗi tham chiếu
     * @return array Mảng dữ liệu key-value
     */
    private static function readCSV($file_path, $required_columns, &$errors)
    {
        $data_array = [];
        $handle = fopen($file_path, 'r');
        if ($handle) {
            $headers = fgetcsv($handle, 0, ','); // 0 để không giới hạn độ dài dòng
            $line_number = 1;

            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                $line_number++;
                if (count($headers) !== count($data)) {
                    $errors[] = "Dòng $line_number: Số cột không khớp với tiêu đề.";
                    continue;
                }

                $row_data = array_combine($headers, $data);
                if ($row_data === false) {
                    $errors[] = "Dòng $line_number: Lỗi xử lý dữ liệu.";
                    continue;
                }

                // Kiểm tra cột thiếu dữ liệu
                foreach ($required_columns as $col) {
                    if (!isset($row_data[$col]) || $row_data[$col] === '') {
                        $errors[] = "Dòng $line_number: Thiếu dữ liệu ở cột '$col'.";
                        continue 2; // Bỏ qua dòng này
                    }
                }

                $data_array[] = $row_data;
            }
            fclose($handle);
        } else {
            $errors[] = 'Không thể mở file CSV!';
        }
        return $data_array;
    }

    /**
     * Generator để đọc file CSV từng dòng (cho dữ liệu lớn)
     * @param string $file_path Đường dẫn file
     * @param array $required_columns Các cột bắt buộc
     * @return Generator
     */
    private static function readCSVGenerator($file_path, $required_columns)
    {
        $handle = fopen($file_path, 'r');
        if ($handle) {
            $headers = fgetcsv($handle, 0, ',');
            $line_number = 1;

            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                $line_number++;
                if (count($headers) !== count($data)) {
                    \Session::set_flash('error', "Dòng $line_number: Số cột không khớp với tiêu đề.");
                    continue;
                }

                $row_data = array_combine($headers, $data);
                if ($row_data === false) {
                    \Session::set_flash('error', "Dòng $line_number: Lỗi xử lý dữ liệu.");
                    continue;
                }

                foreach ($required_columns as $col) {
                    if (!isset($row_data[$col]) || $row_data[$col] === '') {
                        \Session::set_flash('error', "Dòng $line_number: Thiếu dữ liệu ở cột '$col'.");
                        continue 2;
                    }
                }

                yield $row_data; // Trả về từng dòng
            }
            fclose($handle);
        }
    }
}
