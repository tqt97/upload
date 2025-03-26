<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import CSV</title>
    <script>
        function uploadFile() {
            let formData = new FormData(document.getElementById('uploadForm'));
            fetch('<?= Uri::create("csv/import") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('filePath').value = data.file;
                    processFile();
                } else {
                    document.getElementById('message').innerText = data.message;
                }
            })
            .catch(error => console.error('Lỗi:', error));
        }

        function processFile() {
            let filePath = document.getElementById('filePath').value;
            let formData = new FormData();
            formData.append('file', filePath);

            let progressBar = document.getElementById('progressBar');
            progressBar.style.width = '0%';

            fetch('<?= Uri::create("csv/process") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const reader = response.body.getReader();
                return new ReadableStream({
                    start(controller) {
                        function push() {
                            reader.read().then(({ done, value }) => {
                                if (done) {
                                    controller.close();
                                    return;
                                }
                                let text = new TextDecoder().decode(value);
                                try {
                                    let json = JSON.parse(text);
                                    if (json.progress) {
                                        progressBar.style.width = json.progress + '%';
                                    } else {
                                        document.getElementById('message').innerText = "Nhập thành công " + json.imported + " dòng!";
                                        if (json.errors.length > 0) {
                                            document.getElementById('errors').innerText = json.errors.join("\n");
                                        }
                                    }
                                } catch (e) {}
                                push();
                            });
                        }
                        push();
                    }
                });
            })
            .catch(error => console.error('Lỗi:', error));
        }
    </script>
    <style>
        #progressContainer { width: 100%; background: #ddd; }
        #progressBar { height: 20px; width: 0%; background: green; text-align: center; color: white; }
    </style>
</head>
<body>
    <h2>Upload CSV</h2>
    <form id="uploadForm" onsubmit="event.preventDefault(); uploadFile();">
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Upload & Import</button>
    </form>

    <input type="hidden" id="filePath">
    
    <div id="progressContainer">
        <div id="progressBar">0%</div>
    </div>

    <div id="message" style="color: red;"></div>
    <pre id="errors" style="color: red;"></pre>
</body>
</html>
