<?php

include('session.php');
include('database.php');
include('includes/companies_helper.php');

$obj = new Database();
companies_ensure_table($obj);

if (isset($_POST['save_company'])) {

    $id = (int) ($_POST['company_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $now = date('Y-m-d H:i:s');

    if ($name === '') {
        companies_toast('error', 'Company name is required.');
    }

    $safe_name = $obj->escape($name);
    $dup_where = "name = '$safe_name'";
    if ($id > 0) {
        $dup_where .= ' AND id != ' . $id;
    }

    if ((int) $obj->count('companies', '*', $dup_where) > 0) {
        companies_toast('error', 'A company with this name already exists.');
    }

    $data = [
        'name' => $safe_name,
        'is_active' => $is_active,
        'updated_at' => $now,
    ];

    if ($id > 0) {
        $obj->select('companies', '*', null, 'id = ' . $id);
        $old_rows = $obj->getResult();
        $old_name = $old_rows[0]['name'] ?? '';

        if ($obj->update('companies', $data, 'id = ' . $id)) {
            if ($old_name !== '' && $old_name !== $name) {
                $obj->update(
                    'add_employee_details',
                    ['company_name' => $safe_name],
                    "company_name = '" . $obj->escape($old_name) . "'"
                );
            }
            companies_toast('success', 'Company updated successfully.');
        }
        companies_toast('error', 'Failed to update company.');
    }

    $data['created_at'] = $now;
    if ($obj->insert('companies', $data)) {
        companies_toast('success', 'Company added successfully.');
    }
    companies_toast('error', 'Failed to add company.');
}

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {
        $obj->select('companies', '*', null, 'id = ' . $id);
        $rows = $obj->getResult();
        $company_name = $rows[0]['name'] ?? '';

        if ($company_name !== '') {
            $in_use = (int) $obj->count(
                'add_employee_details',
                '*',
                "company_name = '" . $obj->escape($company_name) . "'"
            );
            if ($in_use > 0) {
                companies_toast('error', 'Cannot delete: ' . $in_use . ' employee(s) use this company. Deactivate it instead.');
            }
        }

        if ($obj->delete('companies', 'id = ' . $id)) {
            companies_toast('success', 'Company deleted successfully.');
        }
        companies_toast('error', 'Failed to delete company.');
    }
}

$edit_company = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $obj->select('companies', '*', null, 'id = ' . $edit_id);
    $rows = $obj->getResult();
    if (!empty($rows) && is_array($rows)) {
        $edit_company = $rows[0];
    }
}

$companies = companies_list_all($obj);
$form_id = $edit_company ? (int) $edit_company['id'] : 0;
$form_name = $edit_company ? $edit_company['name'] : '';
$form_active = $edit_company ? (int) $edit_company['is_active'] : 1;

?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dhothar International" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Employee Companies | Dhothar International</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>

    <style>
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }
        .badge-active { background: #27ae60; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .badge-inactive { background: #e74c3c; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .company-form-box { background: #f9f9f9; border: 1px solid #e5e5e5; padding: 18px; margin-bottom: 24px; border-radius: 4px; }
    </style>
</head>

<body class="">
<div class="page-container">
    <div class="sidebar-menu">
        <?php include('components/sidebar.php'); ?>
    </div>

    <div class="main-content">
        <?php include('components/header.php'); ?>
        <hr />

        <div class="page-header-row">
            <h3>
                <i class="entypo-briefcase"></i>
                Employee Companies
            </h3>
        </div>

        <div class="company-form-box">
            <h4 style="margin-top:0;">
                <?= $form_id > 0 ? 'Edit Employee Company' : 'Add New Employee Company' ?>
            </h4>

            <form method="post" action="">
                <input type="hidden" name="company_id" value="<?= $form_id ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. RENT WITH ME S.R.L"
                                   value="<?= htmlspecialchars($form_name) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Active</label><br>
                            <label style="font-weight:normal; margin-top:6px;">
                                <input type="checkbox" name="is_active" value="1"
                                    <?= $form_active ? 'checked' : '' ?>>
                                Yes
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" name="save_company" class="btn btn-blue btn-sm">
                    <?= $form_id > 0 ? 'Update Company' : 'Add Company' ?>
                </button>

                <?php if ($form_id > 0): ?>
                    <a href="manage_companies" class="btn btn-default btn-sm">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <h4>All Employee Companies</h4>
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Company Name</th>
                <th>Status</th>
                <th style="width:140px;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($companies)): ?>
                <tr>
                    <td colspan="4" class="text-center">No companies yet. Add one above.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($companies as $company): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($company['name']) ?></td>
                        <td>
                            <?php if ((int) $company['is_active'] === 1): ?>
                                <span class="badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="manage_companies?edit=<?= (int) $company['id'] ?>"
                               class="btn btn-default btn-xs">Edit</a>
                            <a href="manage_companies?delete=<?= (int) $company['id'] ?>"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this company?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <?php include('components/footer.php'); ?>
    </div>
</div>

<script src="<?= $base_url ?>assets/js/gsap/TweenMax.min.js"></script>
<script src="<?= $base_url ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
<script src="<?= $base_url ?>assets/js/bootstrap.js"></script>
<script src="<?= $base_url ?>assets/js/joinable.js"></script>
<script src="<?= $base_url ?>assets/js/resizeable.js"></script>
<script src="<?= $base_url ?>assets/js/neon-api.js"></script>
<script src="<?= $base_url ?>assets/js/cookies.min.js"></script>
<script src="<?= $base_url ?>assets/js/neon-custom.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<?php if (isset($_SESSION['toast'])): ?>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: '3000'
        };
        toastr["<?= $_SESSION['toast']['type'] ?>"]("<?= htmlspecialchars($_SESSION['toast']['message'], ENT_QUOTES) ?>");
    </script>
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

</body>
</html>
