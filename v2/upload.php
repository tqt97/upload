<form action="/bulk/upload" method="post" enctype="multipart/form-data">
    <div>
        <label for="file_input">Chọn file CSV:</label>
        <input type="file" name="file_input" id="file_input" accept=".csv" required>
    </div>
    <div>
        <input type="submit" value="Upload">
    </div>
</form>

<?php
if ($error = \Session::get_flash('error')) {
    echo '<p style="color: red;">' . implode('<br>', (array) $error) . '</p>';
}
if ($success = \Session::get_flash('success')) {
    echo '<p style="color: green;">' . $success . '</p>';
}
?>
