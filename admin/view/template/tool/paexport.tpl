<?=$header?>
<?=$column_left?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <?php if ($error) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <?php if ($success) { ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" form="form-backup" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <ul class="nav nav-tabs">
            <li class="<?=($tab=='export'?'active':'')?>">
                <a href="#tab-export" data-toggle="tab"><?php echo $tab_export; ?></a>
            </li>
            <li class="<?=($tab=='import'?'active':'')?>">
                <a href="#tab-import" data-toggle="tab"><?php echo $tab_import; ?></a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane <?=($tab=='export'?'active in':'fade')?>" id="tab-export">
                <form action="<?php echo $export; ?>" method="post" id="form-export" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <i class="fa fa-check-box"></i> <?php echo $text_select_cats; ?>
                                        (<?=count($categories)?>)</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label"></label>
                                        <div class="col-sm-10">
                                            <div class="well well-sm" style="height: 460px; overflow: auto;">
                                                <?php foreach ($categories as $category) { ?>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="categories[]"
                                                               value="<?php echo $category['category_id']; ?>"
                                                               checked="checked"/>
                                                        <?php echo $category['name']; ?>
                                                    </label>
                                                </div>
                                                <?php } ?>
                                            </div>
                                            <a onclick="$(this).parent().find(':checkbox').prop('checked', true);"><?php echo $text_select_all; ?></a>
                                            /
                                            <a onclick="$(this).parent().find(':checkbox').prop('checked', false);"><?php echo $text_unselect_all; ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <i class="fa fa-check-box"></i> <?php echo $text_select_attrs; ?>
                                        (<?=count($attributes)?>)
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label"></label>
                                        <div class="col-sm-10">
                                            <div class="well well-sm" style="height: 460px; overflow: auto;">
                                                <?php foreach ($attributes as $attribute) { ?>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="attribute[]"
                                                               value="<?php echo $attribute['attribute_id']; ?>"
                                                               checked="checked"/>
                                                        <?php echo $attribute['name']; ?></label>
                                                </div>
                                                <?php } ?>
                                            </div>
                                            <a onclick="$(this).parent().find(':checkbox').prop('checked', true);"><?php echo $text_select_all; ?></a>
                                            /
                                            <a onclick="$(this).parent().find(':checkbox').prop('checked', false);"><?php echo $text_unselect_all; ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="pull-right">
                                <button data-toggle="tooltip" title="<?php echo $title_export; ?>"
                                        class="btn btn-primary"><i class="fa fa-download"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane <?=($tab=='import'?'active in':'fade')?>" id="tab-import">

                <div class="row">
                    <div class="col-md-9">
                        <form action="<?php echo $import; ?>" method="post" enctype="multipart/form-data"
                              id="form-import" class="form-horizontal">
                            <div class="form-inline">
                                <input type="file" name="import"
                                       class="form-control" <?php echo ($import ? '':'disabled'); ?> required>
                                <button data-toggle="tooltip" title="<?php echo $title_import; ?>"
                                        class="btn btn-primary <?php echo ($import ? '':'disabled'); ?>"><i
                                            class="fa fa-upload"></i></button>
                            </div>
                        </form>
                        <div class="alert alert-info" style="max-height: 460px; overflow-y: scroll;"><?=nl2br(file_get_contents(DIR_SYSTEM. '../tmp/import.log'))?></div>
                    </div>
                    <div class="col-md-3">
                        <h3><?php echo $text_backup; ?></h3>
                        <a href="<?php echo $backup; ?>" data-toggle="tooltip"
                           title="<?php echo ($backup ? $title_backup_do : $title_backup_done); ?>"
                           class="btn btn-primary"><i class="fa fa-copy"></i></a>
                        <a href="<?php echo $restore; ?>" data-toggle="tooltip"
                           title="<?php echo ($restore ? $title_restore_do : $title_restore_done); ?>"
                           class="btn btn-primary <?php echo ($restore ? '':'disabled'); ?>"><i
                                    class="fa fa-download"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?=$footer?>