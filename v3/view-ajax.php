<!DOCTYPE html>
<html>
<head>
    <title>Import File</title>
    <style>
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div>
        <label for="file_input">Chọn file (chỉ hỗ trợ CSV):</label>
        <input type="file" id="file_input" accept=".csv" required>
    </div>
    <div>
        <button onclick="uploadFile()">Upload</button>
    </div>
    <div id="response"></div>

    <script>
        function uploadFile() {
            const fileInput = document.getElementById('file_input');
            const file = fileInput.files[0];
            const responseDiv = document.getElementById('response');

            if (!file) {
                responseDiv.innerHTML = '<p class="error">Vui lòng chọn file!</p>';
                return;
            }

            const formData = new FormData();
            formData.append('file_input', file);

            fetch('/import/import', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    responseDiv.innerHTML = `<p class="success">${data.message}</p>`;
                } else {
                    const errors = data.errors.map(err => `<p class="error">${err}</p>`).join('');
                    responseDiv.innerHTML = errors;
                }
            })
            .catch(error => {
                responseDiv.innerHTML = `<p class="error">Lỗi: ${error.message}</p>`;
            });
        }
    </script>
</body>
</html>
