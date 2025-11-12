<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator with Template</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="qr-form-body">
    <div class="qr-container">
        <h1>QR Code Generator with Template</h1>
        
        <div class="qr-info-section">
            <h3>How to use:</h3>
            <ol>
                <li>Upload an Excel file (.xlsx) with QR code strings in column A</li>
                <li>Enter the GTIN (Global Trade Item Number)</li>
                <li>Specify how many items will be packed per carton</li>
                <li>Upload a Word document template for the stickers</li>
                <li>Click "Generate" to create QR codes and apply them to your template</li>
            </ol>
            <p><strong>Need a sample file?</strong> <a href="create_sample.php" target="_blank">Click here to generate a sample Excel file</a></p>
        </div>
        
        <form id="qrForm" action="process.php" method="post" enctype="multipart/form-data" class="qr-form">
            <div class="qr-form-group">
                <label for="xlsx_file">Excel/CSV File with QR Code Strings <span class="qr-required">*</span></label>
                <input type="file" id="xlsx_file" name="xlsx_file" accept=".xlsx,.xls,.csv" required>
                <div class="qr-file-info">Upload an Excel file (.xlsx or .xls) or CSV file containing the list of QR code strings</div>
            </div>

            <div class="qr-form-group">
                <label for="gtin">GTIN <span class="qr-required">*</span></label>
                <input type="text" id="gtin" name="gtin" placeholder="Enter GTIN number" required>
                <div class="qr-file-info">Global Trade Item Number (usually 13-14 digits)</div>
            </div>

            <div class="qr-form-group">
                <label for="items_per_carton">Items per Carton <span class="qr-required">*</span></label>
                <input type="number" id="items_per_carton" name="items_per_carton" min="1" placeholder="Enter number of items per carton" required>
                <div class="qr-file-info">Number of individual items that will be packed in each carton</div>
            </div>

            <div class="qr-form-group">
                <label for="docx_template">Word Document Template <span class="qr-required">*</span></label>
                <input type="file" id="docx_template" name="docx_template" accept=".docx" required>
                <div class="qr-file-info">Upload a Word document (.docx) template for the stickers</div>
            </div>

            <div class="qr-form-group">
                <label for="barcode_type">Barcode Type</label>
                <select id="barcode_type" name="barcode_type">
                    <option value="DATAMATRIX">Data Matrix (recommended)</option>
                    <option value="QRCODE,L">QR Code (Low error correction)</option>
                    <option value="QRCODE,M">QR Code (Medium error correction)</option>
                    <option value="QRCODE,Q">QR Code (Quartile error correction)</option>
                    <option value="QRCODE,H">QR Code (High error correction)</option>
                </select>
                <div class="qr-file-info">Choose the type of barcode to generate</div>
            </div>

            <button type="submit" class="qr-submit-btn" id="submitBtn">
                Generate QR Codes and Apply Template
            </button>
        </form>

        <div class="qr-progress" id="progress">
            <p>Processing your request...</p>
            <div class="qr-progress-bar">
                <div class="qr-progress-fill" id="progressFill"></div>
            </div>
            <p id="progressText">0%</p>
        </div>

        <div id="result"></div>
    </div>

    <script>
        document.getElementById('qrForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            const xlsxFile = document.getElementById('xlsx_file').files[0];
            const gtin = document.getElementById('gtin').value.trim();
            const itemsPerCarton = document.getElementById('items_per_carton').value;
            const docxTemplate = document.getElementById('docx_template').files[0];
            
            if (!xlsxFile || !gtin || !itemsPerCarton || !docxTemplate) {
                showError('Please fill in all required fields.');
                return;
            }
            
            // Validate GTIN (basic check)
            if (!/^\d{12,14}$/.test(gtin)) {
                showError('GTIN must be 12-14 digits.');
                return;
            }
            
            // Validate file types
            if (!xlsxFile.name.match(/\.(xlsx|xls|csv)$/i)) {
                showError('File must be .xlsx, .xls, or .csv format.');
                return;
            }
            
            if (!docxTemplate.name.match(/\.docx$/i)) {
                showError('Template file must be .docx format.');
                return;
            }
            
            // Show progress
            showProgress();
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Invalid JSON response: ${text.substring(0, 200)}...`);
                    }
                });
            })
            .then(data => {
                hideProgress();
                if (data.success) {
                    let message = data.message;
                    if (data.stats) {
                        message += `<br><br><strong>Processing Summary:</strong><br>
                        • Total QR strings processed: ${data.stats.total_strings}<br>
                        • QR codes generated: ${data.stats.total_qr_codes}<br>
                        • Items per carton: ${data.stats.items_per_carton}`;
                    }
                    
                    message += `<br><br><strong>Download Options:</strong><br>
                    <a href="${data.download_url}&type=html" target="_blank" style="display: inline-block; margin: 5px; padding: 8px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 3px;">View Report</a>
                    <a href="${data.download_url}&type=zip" style="display: inline-block; margin: 5px; padding: 8px 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 3px;">Download ZIP</a>
                    <a href="${data.download_url}&type=txt" style="display: inline-block; margin: 5px; padding: 8px 15px; background: #FF9800; color: white; text-decoration: none; border-radius: 3px;">Download Text</a>`;
                    
                    showSuccess(message);
                } else {
                    showError(data.message || 'An error occurred while processing your request.');
                }
            })
            .catch(error => {
                hideProgress();
                showError('Network error: ' + error.message);
            });
        });
        
        function showProgress() {
            document.getElementById('progress').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            
            // Simulate progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                
                document.getElementById('progressFill').style.width = progress + '%';
                document.getElementById('progressText').textContent = Math.round(progress) + '%';
            }, 500);
            
            // Store interval ID for cleanup
            window.progressInterval = interval;
        }
        
        function hideProgress() {
            if (window.progressInterval) {
                clearInterval(window.progressInterval);
            }
            document.getElementById('progress').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
            
            // Complete progress bar
            document.getElementById('progressFill').style.width = '100%';
            document.getElementById('progressText').textContent = '100%';
        }
        
        function showError(message) {
            const result = document.getElementById('result');
            result.innerHTML = '<div class="qr-error">' + message + '</div>';
        }
        
        function showSuccess(message) {
            const result = document.getElementById('result');
            result.innerHTML = '<div class="qr-success">' + message + '</div>';
        }
        
        // File size validation
        document.getElementById('xlsx_file').addEventListener('change', function() {
            validateFileSize(this, 50); // 50MB limit for Excel files
        });
        
        document.getElementById('docx_template').addEventListener('change', function() {
            validateFileSize(this, 50); // 50MB limit for Word documents
        });
        
        function validateFileSize(input, maxSizeMB) {
            if (input.files[0]) {
                const fileSize = input.files[0].size / 1024 / 1024; // Convert to MB
                if (fileSize > maxSizeMB) {
                    showError(`File "${input.files[0].name}" is too large (${fileSize.toFixed(2)}MB). Maximum allowed size is ${maxSizeMB}MB. Please reduce the file size and try again.`);
                    input.value = ''; // Clear the input
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>