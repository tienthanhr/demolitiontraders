<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabins - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Cabins & Portable Buildings</h1>
            <nav class="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <span>Cabins</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <h2 style="margin-bottom:24px;">Thinking of building a cabin? Building cabins to sell?</h2>
            <p>Demolition Traders has the range of products to get the job started.</p>

            <h3><a href="<?php echo userUrl('shop.php?category=interior-linings'); ?>">Interior Linings</a></h3>
            <ul>
                <li>Okoume Poplar Core is finished with a hardwood veneer, perfect for adding a rustic feel or a spot of character.</li>
                <li>Grooved plywood</li>
                <li>Poplar Pine is an excellent value plywood. A clean pine veneer paired with stable poplar core this board is very versatile.</li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=flooring-plywood'); ?>">Flooring Plywood</a></h3>
            <ul>
                <li>19mm Treated Flooring Plywood</li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=exterior-cladding'); ?>">Exterior Cladding</a></h3>
            <ul>
                <li>Utility clad Seconds grade Shadow clad, quick and efficient way to line the outside of your cabin</li>
                <li>Weather Board New seconds and pre primed pine, classic look to match in with the house</li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=aluminium-joinery'); ?>">New Aluminium Joinery</a></h3>
            <p>Brand new aluminium joinery, we order in advance so you don't have to, browse through our range of new joinery to pick the items that will best match your design. Available in four colours, Arctic White, Silver Pearl, Matte Black or Ironsand.</p>
            <ul>
                <li><a href="<?php echo userUrl('shop.php?category=doors'); ?>">Doors</a></li>
                <li><a href="<?php echo userUrl('shop.php?category=sliding-doors'); ?>">Sliding Doors</a></li>
                <li><a href="<?php echo userUrl('shop.php?category=windows'); ?>">Windows</a></li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=kitchenettes'); ?>">Kitchenettes</a></h3>
            <p>New Kitchenettes in a variety of sizes to suit. Stainless steel benchtops available or you are welcome to source another top to fit your style.</p>
            <ul>
                <li>Units</li>
                <li>Stainless Steel tops</li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=framing-timber'); ?>">Framing Timber</a></h3>
            <ul>
                <li>SG8 framing timber, lengths may vary depending on the pack.</li>
                <li>90 x 45 H3.2</li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=roofing'); ?>">Roofing Iron</a></h3>
            <ul>
                <li>New Zincalume roofing iron available.</li>
                <li>Zinc</li>
            </ul>

            <h3><a href="<?php echo userUrl('shop.php?category=flashings'); ?>">Flashing</a></h3>
            <p>New flashings available for the roof and the joinery. Head flashings available in matching colours to the new joinery.</p>
            <ul>
                <li>Head Flashing</li>
                <li>Ridge Capping</li>
                <li>Barge Flashing</li>
                <li>Corner Flashing</li>
            </ul>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
