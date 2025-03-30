<?php
namespace Service;

class ImportService
{
    /**
     * Xử lý import file và trả về mảng dữ liệu
     * @param array $file Thông tin file từ Input::file()
     * @return array Mảng dữ liệu đọc từ file hoặc rỗng nếu lỗi
     */
    public static function importFile($file)
    {
        $data_array = [];
        $config = [
            'path' => DOCROOT . 'uploads',
            'ext_whitelist' => ['csv'], // Hiện tại chỉ hỗ trợ CSV
        ];

        // Xử lý upload file
        \Upload::process($config);
        if (\Upload::is_valid()) {
            \Upload::save();
            $uploaded_file = \Upload::get_files(0);
            $file_path = $uploaded_file['saved_to'] . $uploaded_file['saved_as'];
            $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

            // Kiểm tra loại file
            if ($file_ext === 'csv') {
                $data_array = static::readCSV($file_path);
            } else {
                \Session::set_flash('error', 'Định dạng file không được hỗ trợ! Chỉ hỗ trợ CSV.');
            }

            // Xóa file tạm
            \File::delete($file_path);
        } else {
            \Session::set_flash('error', \Upload::get_errors());
        }

        return $data_array;
    }

    /**
     * Đọc file CSV và trả về mảng dữ liệu
     * @param string $file_path Đường dẫn file
     * @return array Mảng dữ liệu key-value
     */
    private static function readCSV($file_path)
    {
        $data_array = [];
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
        }
        return $data_array;
    }
}
