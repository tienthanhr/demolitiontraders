<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell to Us - Demolition Traders Hamilton</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom date picker styling */
        input[type="date"] {
            position: relative;
            padding: 12px 15px;
            font-size: 14px;
            border: 2px solid #ddd;
            border-radius: 4px;
            background: white;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        input[type="date"]:hover {
            border-color: #2f3192;
        }
        
        input[type="date"]:focus {
            outline: none;
            border-color: #2f3192;
            box-shadow: 0 0 0 3px rgba(47, 49, 146, 0.1);
        }
        
        /* Style for the calendar icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            opacity: 0.6;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
            background: #f0f0f0;
        }
        
        /* Add icon styling for date field */
        .date-input-wrapper {
            position: relative;
        }
        
        .date-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #2f3192;
            pointer-events: none;
        }
        
        .date-input-wrapper input[type="date"] {
            padding-left: 45px;
        }
        
        /* Hide placeholder on focus */
        input[type="date"]:focus::-webkit-datetime-edit-text,
        input[type="date"]:focus::-webkit-datetime-edit-month-field,
        input[type="date"]:focus::-webkit-datetime-edit-day-field,
        input[type="date"]:focus::-webkit-datetime-edit-year-field {
            color: #333;
        }
        
        input[type="date"]:not(:focus):not(:valid) {
            color: #999;
        }
        
        /* Photo preview styling */
        .photo-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .photo-preview-item {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            aspect-ratio: 1;
            transition: all 0.3s ease;
        }
        
        .photo-preview-item:hover {
            border-color: #2f3192;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .photo-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .photo-preview-item .remove-photo {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .photo-preview-item .remove-photo:hover {
            background: #dc3545;
            transform: scale(1.1);
        }
        
        .photo-preview-item .photo-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px;
            font-size: 12px;
            text-align: center;
        }
        
        .btn.btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn.btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Sell to Us</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Sell to Us</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="two-column-layout">
                <div class="content-main">
                    <h2>Turn Your Unwanted Materials Into Cash</h2>
                    <p>Demolition Traders is always looking to purchase quality building materials. Whether you're a demolition contractor, builder, or homeowner with materials from a renovation, we'd love to hear from you.</p>
                    
                    <h3>What We Buy</h3>
                    <div class="icon-grid">
                        <div class="icon-box">
                            <i class="fa-solid fa-door-open"></i>
                            <h4>Doors & Windows</h4>
                            <p>Interior, exterior, sliding doors, French doors, aluminium and wooden windows</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-tree"></i>
                            <h4>Native Timber</h4>
                            <p>Rimu, kauri, matai, and other native timber in any form</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-bath"></i>
                            <h4>Bathroom Items</h4>
                            <p>Baths, vanities, toilets, hand basins, and bathroom fittings</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-kitchen-set"></i>
                            <h4>Kitchen Cabinets</h4>
                            <p>Complete kitchens or individual cabinets in good condition</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-house"></i>
                            <h4>Roofing Iron</h4>
                            <p>Corrugated iron, long-run, and other roofing materials</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <h4>Building Materials</h4>
                            <p>Weatherboards, plywood, bricks, and more</p>
                        </div>
                    </div>
                    
                    <h3>How It Works</h3>
                    <ol class="numbered-list">
                        <li>Contact us with photos and description of your materials</li>
                        <li>We'll assess the items and provide a quote</li>
                        <li>If you accept, we can arrange pickup or you can deliver to our yard</li>
                        <li>Get paid for your materials!</li>
                    </ol>
                    
                    <div class="info-box-yellow">
                        <h4>What We Look For</h4>
                        <ul>
                            <li>Good quality materials in reusable condition</li>
                            <li>Complete items (e.g. doors with frames, windows with glass)</li>
                            <li>Character items from older buildings</li>
                            <li>Native timber in any form</li>
                            <li>Reasonable quantities</li>
                        </ul>
                    </div>
                    
                    <h3>Submit Your Items</h3>
                    <form class="contact-form" id="sell-form" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Your Name *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" placeholder="City or region">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Item Name *</label>
                                <input type="text" name="item_name" placeholder="e.g., Rimu Doors, Weatherboards, Kitchen Cabinets" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity *</label>
                                <input type="text" name="quantity" placeholder="e.g., 10 units, 50 sqm" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fa-solid fa-calendar-days"></i> Preferred Pick Up Date</label>
                                <div class="date-input-wrapper">
                                    <i class="fa-solid fa-calendar"></i>
                                    <input type="date" name="pickup_date" min="<?php echo date('Y-m-d'); ?>" placeholder="dd/mm/yyyy">
                                </div>
                                <small style="color: #666; display: block; margin-top: 5px;">
                                    <i class="fa-solid fa-info-circle"></i> Optional - Select your preferred collection date
                                </small>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-truck"></i> Pick Up or Delivery *</label>
                                <select name="pickup_delivery" required>
                                    <option value="">Select Option</option>
                                    <option value="pickup_onsite">We pick up from your site</option>
                                    <option value="deliver_to_us">I will deliver to your yard</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="description" rows="6" placeholder="Please describe the materials in detail, including approximate age, any special features, and current condition..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fa-solid fa-camera"></i> Upload Photos</label>
                            <input type="file" id="photo-upload" name="photos[]" multiple accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('photo-upload').click()">
                                <i class="fa-solid fa-upload"></i> Choose Photos
                            </button>
                            <small style="display: block; margin-top: 8px; color: #666;">
                                <i class="fa-solid fa-info-circle"></i> You can select up to 5 photos (JPG, PNG)
                            </small>
                            <div id="photo-preview" class="photo-preview-container"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit for Quote</button>
                    </form>
                </div>
                
                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h3>Contact Us</h3>
                        <p><strong>Phone:</strong><br>07 847 4989</p>
                        <p><strong>Freephone:</strong><br>0800 DEMOLITION</p>
                        <p><strong>Email:</strong><br>info@demolitiontraders.co.nz</p>
                    </div>
                    
                    <div class="sidebar-box">
                        <h3>Our Location</h3>
                        <p>249 Kahikatea Drive, Greenlea Lane<br>Frankton, Hamilton<br>New Zealand</p>
                        <a href="contact.php" class="btn btn-secondary btn-sm">Get Directions</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/toast-notification.php'; ?>
    
    <script>
        // Photo preview and management
        let selectedFiles = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            const photoInput = document.getElementById('photo-upload');
            const previewContainer = document.getElementById('photo-preview');
            
            // Handle file selection
            photoInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                
                // Check if adding these files would exceed the limit
                if (selectedFiles.length + files.length > 5) {
                    showToast('Maximum 5 photos allowed', 'warning');
                    return;
                }
                
                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        selectedFiles.push(file);
                        displayPhotoPreview(file);
                    }
                });
                
                // Clear the input so the same file can be selected again if needed
                photoInput.value = '';
            });
            
            function displayPhotoPreview(file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const photoItem = document.createElement('div');
                    photoItem.className = 'photo-preview-item';
                    photoItem.dataset.fileName = file.name;
                    
                    photoItem.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                        <button type="button" class="remove-photo" onclick="removePhoto('${file.name}')">
                            <i class="fa-solid fa-times"></i>
                        </button>
                        <div class="photo-info">
                            ${formatFileSize(file.size)}
                        </div>
                    `;
                    
                    previewContainer.appendChild(photoItem);
                };
                
                reader.readAsDataURL(file);
            }
            
            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }
        });
        
        // Remove photo function (global scope for onclick)
        window.removePhoto = function(fileName) {
            // Remove from selectedFiles array
            selectedFiles = selectedFiles.filter(file => file.name !== fileName);
            
            // Remove preview item
            const previewItem = document.querySelector(`.photo-preview-item[data-file-name="${fileName}"]`);
            if (previewItem) {
                previewItem.style.opacity = '0';
                previewItem.style.transform = 'scale(0.8)';
                setTimeout(() => previewItem.remove(), 300);
            }
            
            showToast('Photo removed', 'info');
        };
        
        // Set up date input to show DD/MM/YYYY format hint
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[type="date"]');
            if (dateInput) {
                // Add a custom attribute for better UX
                dateInput.setAttribute('data-placeholder', 'dd/mm/yyyy');
                
                // Show formatted date in helper text when date is selected
                dateInput.addEventListener('change', function() {
                    if (this.value) {
                        const date = new Date(this.value);
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const year = date.getFullYear();
                        
                        const helperText = this.parentElement.nextElementSibling;
                        if (helperText && helperText.tagName === 'SMALL') {
                            helperText.innerHTML = `<i class="fa-solid fa-check-circle" style="color: #28a745;"></i> Pick up date: ${day}/${month}/${year}`;
                        }
                    }
                });
            }
        });
        
        document.getElementById('sell-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(this);
            
            // Remove the original photos[] field and add selected files
            formData.delete('photos[]');
            selectedFiles.forEach((file, index) => {
                formData.append('photos[]', file);
            });
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/sell-to-us/submit.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    showToast('Thank you! Your submission has been received. We\'ll review your items and contact you shortly.', 'success');
                    this.reset();
                    
                    // Clear photo preview
                    selectedFiles = [];
                    document.getElementById('photo-preview').innerHTML = '';
                } else {
                    showToast(result.error || 'Failed to submit. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    </script>
</body>
</html>
