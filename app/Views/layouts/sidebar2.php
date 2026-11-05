<!-- ========== App Menu ========== -->
<style>
    @import url('https://fonts.cdnfonts.com/css/skid-rock');
    #menu-search::placeholder {
        color: #fff; /* Set placeholder text color to white */
        opacity: 0.7; /* Optional: Adjust opacity for better visibility */
    }
</style>


<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="/" class="logo logo-dark">
            <span class="logo-sm">
                <img class="rounded-5" src="<?= base_url() ?>assets/images/logo-sm.png" alt="" height="50">
            </span>
            <span class="logo-lg">
                <!-- <img src="<?= base_url() ?>assets/images/logo-dark.png" alt="" height="60"> -->
                <h1 class="text-white pt-3" style="font-family: 'Skid Rock', sans-serif; font-size: 32px;">Avenir Holidays</h1>
            </span>
        </a>
        <!-- Light Logo-->
        <a href="/" class="logo logo-light">
            <span class="logo-sm">
                <img class="rounded-5" src="<?= base_url() ?>assets/images/logo-sm.png" alt="" height="50">
            </span>
            <span class="logo-lg">
                <!-- <img src="<?= base_url() ?>assets/images/logo-light.png" alt="" height="60"> -->
                <h1 class="text-white pt-3" style="font-family: 'Skid Rock', sans-serif; font-size: 32px;">Avenir Holidays</h1>
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>
    <br>
    <br>
    <div class="search-box">
        <input type="text" id="menu-search"  placeholder="Search menu..." style="background: 0 0;
        border : none;
        padding-left: 0;
        padding-right: 0;
        border-bottom: 1px solid rgba(255,255,255,.2);
        color: #fff;
        border-radius: 0; margin-left: 5%;outline: none;" >
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="menu-list">

                <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="<?= base_url(); ?>dashboard">
                        <i class="ri-dashboard-2-line"></i> <span class="fw-semibold" data-key="t-widgets">Ground Handling</span>
                    </a>
                </li>
                <?php
                $x = 1;
                foreach ($allowed_categories as $category) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link" href="#<?= $x; ?>" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="<?= $x; ?>">
                            <?php
                            $icon = '<i class="ri-dashboard-2-line"></i>';
                            switch ($category['category']) {
                                case 'Tour':
                                $icon = '<i class="ri-road-map-line"></i>';
                                break;
                                case 'Master Files':
                                $icon = '<i class="ri-file-list-3-line"></i>';
                                break;
                                case 'Settings':
                                $icon = '<i class="ri-settings-line"></i>';
                                break;
                                case 'Staff Directory':
                                $icon = '<i class="ri-user-2-line"></i>';
                                break;
                                case 'Accounts':
                                $icon = '<i class="ri-money-dollar-circle-line"></i>';
                                break;
                                case 'Accounts Reports':
                                $icon = '<i class="ri-file-chart-line"></i>';
                                break;
                                case 'Chat':
                                $icon = '<i class="ri-chat-1-line"></i>';
                                break;
                            }
                            ?>
                            <?= $icon; ?> <span class="fw-semibold" data-key="<?= 't-' . $x; ?>"><?= $category['category']; ?></span>
                        </a>
                        <div class="collapse menu-dropdown" id="<?= $x; ?>">
                            <ul class="nav nav-sm flex-column">

                                <?php
                                foreach ($allowed_menu_list as $menu_item) {
                                    if ($category['cat_id'] == $menu_item['cat_id']) { ?>
                                        <li class="nav-item">
                                            <a href="<?= base_url() . $menu_item['url']; ?>" class="nav-link" data-key="<?= 't-' . $x; ?>"> <?= $menu_item['menu']; ?> </a>
                                        </li>
                                    <?php }
                                }
                                ?>
                            </ul>
                        </div>
                    </li> <!-- end Dashboard Menu -->
                    <?php $x++;
                } ?>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>

<script type="text/javascript">
   document.addEventListener('DOMContentLoaded', function () {
    var menuSearchInput = document.getElementById('menu-search');
    menuSearchInput.addEventListener('input', function () {
        var filter = menuSearchInput.value.toUpperCase();
        var menuList = document.getElementById('menu-list');
        var items = menuList.getElementsByTagName('li');

        for (var i = 0; i < items.length; i++) {
            var categoryLink = items[i].getElementsByTagName('a')[0];

            // Check if the category link exists
            if (categoryLink) {
                var categoryText = categoryLink.textContent || categoryLink.innerText;
                var menuLinks = items[i].querySelectorAll('.nav-sm .nav-link');
                var foundInCategory = categoryText.toUpperCase().indexOf(filter) > -1;
                var foundInMenu = false;

                // Check if filter matches any menu items under this category
                for (var j = 0; j < menuLinks.length; j++) {
                    var menuItemText = menuLinks[j].textContent || menuLinks[j].innerText;
                    if (menuItemText.toUpperCase().indexOf(filter) > -1) {
                        foundInMenu = true;
                        break; // Stop checking once we find a match
                    }
                }

                // Show or hide category based on filter match
                if (foundInMenu || foundInCategory) {
                    items[i].style.display = ""; // Show the category
                    // Expand the dropdown if there is a matching menu item
                    var dropdown = items[i].querySelector('.collapse');
                    if (dropdown) {
                        dropdown.classList.add('show'); // Ensure dropdown is shown
                    }
                } else {
                    // If no matching menu item, hide the category
                    items[i].style.display = "none"; // Hide the category if no matches
                }
            } else {
                items[i].style.display = "none"; // Hide if no category link
            }
        }

        // Collapse all dropdowns if input is empty
        if (filter === "") {
            var expandedDropdowns = menuList.querySelectorAll('.collapse.show');
            expandedDropdowns.forEach(function (dropdown) {
                dropdown.classList.remove('show'); // Collapse the dropdown
            });
        }
    });
});









</script>
