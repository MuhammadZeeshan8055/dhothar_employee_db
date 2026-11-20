<?php
$displayName = htmlspecialchars($currentUser['name'] ?? 'User');
$firstName = trim(explode(' ', $currentUser['name'] ?? 'User')[0]);
?>
<div class="row dh-app-header">
    <div class="col-md-6 col-sm-8 clearfix">
        <ul class="user-info pull-left pull-none-xsm">
            <li class="profile-info dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="<?= $base_url ?>assets/images/user.jpg" alt class="img-circle" width="44" />
                    <?= $displayName ?>
                </a>
            </li>
        </ul>
    </div>
    <div class="col-md-6 col-sm-4 clearfix hidden-xs">
        <ul class="list-inline links-list pull-right">
            <li>
                <button type="button" id="dh-theme-toggle" class="dh-theme-toggle" title="Toggle light / dark" aria-label="Toggle theme">
                    <svg class="dh-icon-sun" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path>
                    </svg>
                    <svg class="dh-icon-moon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.2 15.1A8.7 8.7 0 0 1 8.9 3.8 9 9 0 1 0 20.2 15.1Z"></path>
                    </svg>
                </button>
            </li>
            <li class="sep"></li>
            <li>
                <a href="<?= $base_url ?>logout">
                    Log Out <i class="entypo-logout right"></i>
                </a>
            </li>
        </ul>
    </div>
</div>
