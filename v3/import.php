<?php
namespace Helper;

class Import
{
    /**
     * Xử lý import file và trả về mảng dữ liệu hoặc generator
     * @param array $file Thông tin file từ Input::file()
     * @param bool $use_generator Sử dụng generator cho dữ liệu lớn
     * @param array $config Cấu hình tùy chỉnh (nếu không truyền, lấy từ file config)
     * @return array|Generator
     */
    public static function importFile($file, $use_generator = false, $config = null)
    {
        $data_array = [];
        $errors = [];

        // Lấy cấu hình mặc định từ file config nếu không truyền config
        $config = $config ?: \Config::load('import', true);
        $required_columns = $config['columns']['required'] ?? [];
        $upload_config = [
            'path' => DOCROOT . 'uploads',
            'ext_whitelist' => ['csv'],
            'max_size' => 10240, // 10MB
        ];

        \Upload::process($upload_config);
        if (\Upload::is_valid()) {
            \Upload::save();
            $uploaded_file = \Upload::get_files(0);
            $file_path = $uploaded_file['saved_to'] . $uploaded_file['saved_as'];
            $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

            if ($file_ext === 'csv') {
                if ($use_generator) {
                    return static::readCSVGenerator($file_path, $config);
                } else {
                    $data_array = static::readCSV($file_path, $config, $errors);
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
     * @param array $config Cấu hình (columns, validation_rules)
     * @param array &$errors Mảng lỗi tham chiếu
     * @return array
     */
    private static function readCSV($file_path, $config, &$errors)
    {
        $data_array = [];
        $required_columns = $config['columns']['required'] ?? [];
        $handle = fopen($file_path, 'r');
        if ($handle) {
            $headers = fgetcsv($handle, 0, ',');
            $line_number = 1;

            // Kiểm tra tiêu đề có chứa các cột bắt buộc không
            foreach ($required_columns as $col) {
                if (!in_array($col, $headers)) {
                    $errors[] = "File CSV thiếu cột bắt buộc: '$col'.";
                    fclose($handle);
                    return [];
                }
            }

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

                // Validate dữ liệu
                if (!static::validateRow($row_data, $config, $line_number, $errors)) {
                    continue;
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
     * Generator để đọc file CSV từng dòng
     * @param string $file_path Đường dẫn file
     * @param array $config Cấu hình
     * @return Generator
     */
    private static function readCSVGenerator($file_path, $config)
    {
        $required_columns = $config['columns']['required'] ?? [];
        $handle = fopen($file_path, 'r');
        if ($handle) {
            $headers = fgetcsv($handle, 0, ',');
            $line_number = 1;

            foreach ($required_columns as $col) {
                if (!in_array($col, $headers)) {
                    \Session::set_flash('error', "File CSV thiếu cột bắt buộc: '$col'.");
                    fclose($handle);
                    return;
                }
            }

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

                $errors = [];
                if (static::validateRow($row_data, $config, $line_number, $errors)) {
                    yield $row_data;
                }
            }
            fclose($handle);
        }
    }

    /**
     * Validate dữ liệu từng dòng
     * @param array $row_data Dữ liệu dòng
     * @param array $config Cấu hình
     * @param int $line_number Số dòng
     * @param array &$errors Mảng lỗi tham chiếu
     * @return bool
     */
    private static function validateRow($row_data, $config, $line_number, &$errors)
    {
        $validation_rules = $config['validation_rules'] ?? [];
        $val = \Validation::forge();

        foreach ($validation_rules as $column => $rules) {
            if (isset($row_data[$column])) {
                $val->add_field($column, $column, $rules);
            }
        }

        if ($val->run($row_data)) {
            return true;
        } else {
            foreach ($val->error() as $field => $error) {
                $errors[] = "Dòng $line_number: Cột '$field' - " . $error->get_message();
            }
            return false;
        }
    }
}
