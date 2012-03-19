<?php
// This view is displayed when active project is selected
?>

<div class="project-title"><div class="title">Code metrics for <?php echo $model->project_name ?> </div>
    <?php
    echo CHtml::link('<div class="main-button"><div class="edit-button">Edit</div></div>', $this->createUrl('project/update', array('id' => $model->project_id)));
    echo CHtml::link('<div class="main-button"><div class="up-button">Projects</div></div>', Yii::app()->createUrl('/project/switchTo'));
    ?>
</div>
<div style="padding-top: 8px; clear: both;">
    <div class="main-button big-button" style="margin-left: 20px; vertical-align: top;"><div id="refresh-button">
            Rescan Project Files
        </div></div>

    <div id="progress-holder" class="working"> </div>
</div>

<div id="project-info">
    
    <div id="piechart" style="margin-top:0px; margin-left:20px; width:400px; height:230px; float: left;"></div>
    <div id="other-stats" style="width: 415px; float: left; margin-left: 25px; padding-top: 10px;">
        <?php
        $this->widget('zii.widgets.CDetailView', array(
            'data' => new File,
            'cssFile' => Yii::app()->baseUrl . '/css/detail-view.css',
            'attributes' => array(
                array(
                    'label' => 'Lines of Code',
                    'type' => 'raw',
                    'value' => '<span id="project-lines-code"></span>'),
                array(
                    'label' => 'Lines of Comments',
                    'type' => 'raw',
                    'value' => '<span id="project-lines-comment"></span>'),
                array(
                    'label' => 'Empty Lines',
                    'type' => 'raw',
                    'value' => '<span id="project-lines-empty"></span>'),
                array(
                    'label' => 'Lines Total',
                    'type' => 'raw',
                    'value' => '<span id="project-lines-total"></span>'),
                array(
                    'label' => 'File Size',
                    'type' => 'raw',
                    'value' => '<span id="project-file-size"></span>'),
                array(
                    'label' => 'Code Size',
                    'type' => 'raw',
                    'value' => '<span id="project-code-size"></span>'),
                array(
                    'label' => 'Number of Functions',
                    'type' => 'raw',
                    'value' => '<span id="project-num-functions"></span>'),
                array(
                    'label' => 'Characters per Line of Code',
                    'type' => 'raw',
                    'value' => '<span id="project-chars-code"></span>'),
            )
        ));
        ?>
    </div>
    <div style="clear: both;"></div>
    <?php
    $this->widget('application.extensions.jqplot.JqplotJsWidget', array('pluginScriptFile' => array('jqplot.pieRenderer.min.js')));
    ?>
    <div id="proj-breadcrumbs"></div>
</div>

<div id="tree-holder" class="tree-holder main-tree-holder" style="margin-top: 20px;">
    <div class="files-title">
        <div style="width: 535px;" class="file-name-holder"><div class="file-name">File Name</div></div>
        <div class="file-column">Total</div>
        <div style="min-width: 70px;" class="file-column">Code</div>
        <div class="file-column">Comments</div>
        <div class="filter-button">Filter</div>
    </div>
</div>

<script type="text/javascript">
    /*<![CDATA[*/
    jQuery(function() {
                
        // get number of files first
        var fileCount = 0;
        var processed = 0;
        
        var running = false;
        
        // jqplot object
        var plot;
        
        // stats for current project or selected folder / file
        var currentData;

        var projectPath = ['<?php echo $model->project_name ?>', '<?php echo $model->getRootFile()->file_id ?>']; // for breadcrumbs
        
        var selectedFileId = <?php echo $model->getRootFile()->file_id ?>;
        
        //
        // view bindings
        //
        
        $("#refresh-button").click(function(){
            indexFiles();
        });
        
        $(".filter-button.add").live('click', function(){
            var fileId = $(this).parent().parent().data('file_id');
            addFilter(fileId);
        });
        
        $(".filter-button.remove").live('click', function(){
            var fileId = $(this).parent().parent().data('file_id');
            removeFilter(fileId);
        });
        
        $(".file-name").live('click', expandTree);
        
        //
        // helper functions
        //
        
        // to keep track about margin of every line in a tree
        $("#tree-holder").data('nesting', 1);
        
        // project breadcrumbs
        $(".proj-bc-file").live('click', function(){
            refreshMainStats($(this).data('file_id'));
            refreshBreadcrumbs($(this).data('file_id'));
        });
        
        
        //  Render one row in file tree
        function renderData(data, $holder){
            var nesting = $holder.data('nesting');

            for (var i=0; i < data.length;i++){
                var $line = $("<div></div>");

                var columns = "<div class='file-name-holder'><div style='margin-left:" + (nesting * 15) + "px;' class='file-name " + data[i].icon + "'>" + data[i].filename + "</div></div>";
                columns += "<div class='file-column c-total'>" + formatNum(data[i].lines_total) + "</div>";
                columns += "<div class='file-column c-code'>" + formatNum(data[i].lines_code) + "</div>";
                columns += "<div class='file-column c-comment'>" + formatNum(data[i].lines_comment) + "</div>";
                if (data[i].filtered == 1){
                    $line.addClass("filtered");
                    // display filter button
                    columns += "<span class='filter-button remove'>&nbsp;</span>";
                }else{
                    columns += "<span class='filter-button add'>&nbsp;</span>";
                }
                var $columnsLine = $("<div>" + columns + "</div>");
                $line.html($columnsLine);
                $line.attr('id', 'file-' + data[i].file_id);
                $line.addClass('file-holder');
                $line.data('filename', data[i].filename);
                $line.data('file_id', data[i].file_id);
                if (data[i].filetype == 'dir'){
                    if (data[i].has_children){
                        $columnsLine.addClass('closed');
                    }else{
                        $columnsLine.addClass('empty');
                    }
                    $columnsLine.addClass('dir-line');
                    $subTree = $("<div class='tree-holder' style='display: none;'>.......</div>");
                    $line.append($subTree);
                    $subTree.data('path', data[i].path + data[i].filename);    
                    $subTree.data('loaded', false);
                    $subTree.data('nesting', nesting + 1);
                }else{
                    $columnsLine.addClass('file-line');
                }
                
                $holder.append($line);
            }
        }
        
        
        //  Add selected file to filter
        function addFilter(fileId){
        
            $.ajax({
                "success": function(data) {
                    // filter handeler recalculates data, but view has to refresh
                    // problem: ajax refresh of all the loaded files
                    // file sturcture up to this level has to be returned
                    var $fileHolder;
                    for (var i=0; i < data.length;i++){
                        $fileHolder = $("#file-" + data[i].file_id);
                        if ($fileHolder.length){
                            $(".c-total:first", $fileHolder).text(data[i].lines_total);
                            $(".c-code:first", $fileHolder).text(data[i].lines_code);
                        }
                    }  
                    $("#file-" + fileId).addClass('filtered');
                    refreshMainStats();
                },
                "dataType": "json",
                "type":"POST",
                "data":"restart="+restart,
                "beforeSend": function( request ) {
                    // loading gif instead of the button
                    var $button = $("#file-" + fileId + " div .filter-button");
                    $button.addClass("remove");
                    $button.removeClass("add");
                },
                "error": function(request,error){
                    // just keep it as it was
                    alert("error");
                },
                "data": "file_id=" + fileId,
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/addFilter') ?>",
                "cache":false
            });
        }
        
        /**
         * Add selected file to filter
         */
        function removeFilter(fileId){
        
            $.ajax({
                "success": function(data) {
                    // filter handeler recalculates data, but view has to refresh
                    // problem: ajax refresh of all the loaded files
                    // file sturcture up to this level has to be returned
                    var $fileHolder;
                    for (var i=0; i < data.length;i++){
                        $fileHolder = $("#file-" + data[i].file_id);
                        if ($fileHolder.length){
                            $(".c-total:first", $fileHolder).text(data[i].lines_total);
                            $(".c-code:first", $fileHolder).text(data[i].lines_code);
                        }
                    }  
                    $("#file-" + fileId).removeClass('filtered');
                    refreshMainStats();
                },
                "dataType": "json",
                "type":"POST",
                "data":"restart="+restart,
                "beforeSend": function( request ) {
                    // loading gif instead of the button
                    var $button = $("#file-" + fileId + " div .filter-button");
                    $button.addClass("add");
                    $button.removeClass("remove");
                },
                "error": function(request,error){
                    // just keep it as it was
                },
                "data": "file_id=" + fileId,
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/removeFilter') ?>",
                "cache":false
            });
        }
        
        /**
         * Loads the project root folde
         */
        function reloadFileView(){
            $tree = $("#tree-holder");
            
            // empty the tree first, but keep labels
            var menu = $(".files-title", $tree).html();
            $tree.html("");
            $tree.append(menu);
            
            $.ajax({
                "success": function(data) {
                    //alert(data);
                    renderData(data, $tree);
                    $("#progress-holder").html("Files loaded").removeClass('working').addClass('ready');
                },
                "dataType": "json",
                "type":"POST",
                //"data":"restart="+restart,
                "beforeSend": function( request ) {
                    $("#progress-holder").html("loading data").removeClass('ready').addClass('working');
                    // loading gif
                },
                "error": function(request,error){
                    filecount = 0;
                    $("#progress-holder").html("Could not get the list of files. Please see the error log.");
                },
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/getDir') ?>",
                "cache":false
            });
        }
        
        function expandTree(){

            var $this = $(this);
            var $lineHolder = $this.parent().parent();
            var $holder = $('.tree-holder', $lineHolder.parent());
            if ($lineHolder.hasClass('dir-line')){
                // data doesn't work
                //alert($holder.data('path'));
                if ($holder.data('loaded') == false){
                    $.ajax({
                        "success": function(data) {
                            //alert(data[0].filename);
                            $holder.html('');
                            renderData(data, $holder);
                            $holder.show('fast');
                            $holder.data('loaded', true);
                            selectedFileId = $lineHolder.parent().data('file_id')
                            refreshMainStats(selectedFileId);
                        },
                        "dataType": "json",
                        "type":"POST",
                        //"data":"restart="+restart,
                        "beforeSend": function( request ) {
                            $holder.slideDown('fast');
                            $holder.html("Scanning files");
                            if ($lineHolder.hasClass('closed')){
                                $lineHolder.addClass('opened');
                                $lineHolder.removeClass('closed');
                            }
                            // loading gif
                        },
                        "error": function(request,error){
                            filecount = 0;
                            $holder.html("Could not get the list of files. Please see the error log.");
                        },
                        "data": "path=" + encodeURI($holder.data('path')),
                        "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/getDir') ?>",
                        "cache":false
                    });
                }else{
                    // data is loaded, just implement fold feature
                    
                    $holder.slideToggle('fast');
                    if ($lineHolder.hasClass('opened')){
                        $lineHolder.addClass('closed');
                        $lineHolder.removeClass('opened');
                    }
                    else if ($lineHolder.hasClass('closed')){
                        $lineHolder.addClass('opened');
                        $lineHolder.removeClass('closed');
                    }
                    selectedFileId = $lineHolder.parent().data('file_id');
                    refreshMainStats(selectedFileId);
                }
            }else{
                selectedFileId = $lineHolder.parent().data('file_id');
                refreshMainStats(selectedFileId);
            }
        }
        
        function recalculateStats(){
            $.ajax({
                "success": function(data) {
                    running = false;
                    $("#progress-holder").html("Done").removeClass('working').addClass('ready');
                    reloadFileView();
                    // refresh project totals
                    refreshMainStats(projectPath[1]);
                },
                "type":"POST",
                "beforeSend": function( ) {
                    $("#progress-holder").html("Calculating <span id='processing-file'></span>");
                    running = true;
                },
                "error": function(){
                    filecount = 0;
                    $("#progress-holder").html("Could not process the files. Please see the error log.");
                    running = false;
                },
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/recalculateStats') ?>",
                "cache":false
            });
        }
        
        // get name of file, which is being processed right now
        // problem with multiple requests
        function updateCurrentFile($holder){
        if (!running) return false;
        console.log('update');
        $.ajax({
                "success": function(data) {
                    $("#processing-file").html( '(' + data + ')');
                    console.log(data);
                    keepUpdatingCurrentFile();
                },
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/getCurrentFile') ?>"
            });
        }
        
        function keepUpdatingCurrentFile(){
            $holder = $("#processing-file");
            setTimeout(updateCurrentFile, 200);
        }
        
        /**
         * When files are found on filesystem and stored to database, we can run processFiles, which will get code metrics from them.
         */
        function processFiles(){
            $.ajax({
                "success": function(data) {
                    if (data == 'done'){
                        recalculateStats();
                    }else{
                        processed += data;
                        $("#progress-holder").html("Analyzing " + processed + " / " + fileCount);
                        processFiles();
                    }
                },
                "type":"POST",
                "beforeSend": function( ) {
                    $("#progress-holder").html("Scanning files, it might take a couple of minutes <span id='processing-file'></span>");
                },
                "error": function(){
                    filecount = 0;
                    $("#progress-holder").html("Could not process the files. Please see the error log.");
                    running = false;
                },
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/process') ?>",
                "cache":false
            });
        }
        
        /**
         * Search for project files and store them to database. This operation might take a while
         */
        function indexFiles(){
            $.ajax({
                "success": function(data) {
                    fileCount = data;
                    processFiles();
                },
                "type":"POST",
                "beforeSend": function( request ) {
                    $("#progress-holder").html("Scanning files <span id='processing-file'></span>").removeClass('ready').addClass('working');
                    running = true;
                    //keepUpdatingCurrentFile();
                },
                "error": function(request,error){
                    filecount = 0;
                    $("#progress-holder").html("Could not scan the files. Please see the error log.");
                },
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/crawl') ?>",
                "cache":false
            });
            return false;
        }
        
        // update main statistics and chart after page load or file click
        function refreshMainStats(fileId){
            if (fileId == undefined){
                fileId = selectedFileId;
            }
            $.ajax({
                "success": function(data) {
                    $("#project-lines-total").text(formatNum(data.lines_total));
                    $("#project-lines-code").text(formatNum(data.lines_code));
                    $("#project-lines-comment").text(formatNum(data.lines_comment));
                    $("#project-lines-empty").text(formatNum(data.lines_empty));
                    $("#project-code-size").text(formatNum(data.code_size));
                    $("#project-file-size").text(formatNum(data.filesize));
                    $("#project-num-functions").text(formatNum(data.num_functions));
                    //alert(data.num_functions);
                    var charsPerLine = 0;
                    if (data.lines_code > 0){
                        charsPerLine = parseInt(data.code_size / data.lines_code);
                    }
                    $("#project-chars-code").text(charsPerLine);
                    currentData = data;
                    drawChart();
                    refreshBreadcrumbs(fileId);
                },
                "type":"POST",
                "dataType": "json",
                "data": "file_id=" + fileId,
                "beforeSend": function( request ) {
                    //$("#progress-holder").html("Scanning files ...");
                },
                "error": function(request,error){
                    filecount = 0;
                    $("#progress-holder").html("Could not scan the files. Please see the error log.");
                },
                "url":"<?php echo Yii::app()->createAbsoluteUrl('/file/getInfo') ?>"
            });
        }
        
        function refreshBreadcrumbs(fileId){
            var $holder = $("#proj-breadcrumbs");
            $holder.html("");
            var $selected = $("#file-" + fileId);
            // start with project
            var $file = $("<div class='proj-bc-file'>" + projectPath[0] + "</div>");
            $file.data('file_id', projectPath[1]);
            $holder.append($file);
            
            var breadcrumbs = new Array(16);
            var i = 0;
            var j = 0;
            
            // and then add files
            if ($selected.length){
                while (!$selected.hasClass('main-tree-holder')){
                    if ($selected.hasClass('file-holder')){
                        //alert($selected.data('filename'));
                        breadcrumbs[i] = new Array();
                        breadcrumbs[i][0] = $selected.data('file_id');
                        breadcrumbs[i][1] = $selected.data('filename')
                        i++;
                    }
                    $selected = $selected.parent();
                }
                
                for (j = i - 1; j >= 0; j --){
                    $file = $("<div class='proj-bc-file'>" + breadcrumbs[j][1] + "</div>");
                    $file.data('file_id', breadcrumbs[j][0]);
                    $holder.append($("<span class='bc-separator'> » </span>"));
                    $holder.append($file);
                }
                
                
            }
            
        }
        
        function drawChart(){
            if (plot){
                plot.destroy();
            }
        
            plot = $.jqplot('piechart', [[[currentData.lines_code + ' Lines of Code', parseInt(currentData.lines_code)],[currentData.lines_comment + ' Lines of Comment', parseInt(currentData.lines_comment)],[currentData.lines_empty + ' Empty Lines',parseInt(currentData.lines_empty)]]], {'seriesDefaults':{'renderer':$.jqplot.PieRenderer,'rendererOptions':{'showDataLabels':true}},'grid':{'drawBorder':false,'drawGridlines':false,'background':'transparent','shadow':false},'legend':{'show':'true','location':'e','placement':'inside'}});
            //plot = $.jqplot('piechart', [[['650 Lines of Code', 25000],['214 Lines of Comment',45454],['16 Empty Lines',200]]], {'seriesDefaults':{'renderer':$.jqplot.PieRenderer,'rendererOptions':{'showDataLabels':true}},'grid':{'drawBorder':false,'drawGridlines':false,'background':'transparent','shadow':false},'legend':{'show':'true','location':'e','placement':'inside'}});

        }
        
        // add thausand delimiter for non-decimal numbers
        function formatNum(number){
            var rgx = /(\d+)(\d{3})/;
            while (rgx.test(number)) {
                number = number.replace(rgx, '$1' + ',' + '$2');
            }
            return number;
        }
        
        // initial rendering
<?php if ($model->getRootFile()->processed) { ?>
            reloadFileView();
            refreshMainStats(projectPath[1]);
<?php } else { ?>
            indexFiles();
<?php } ?>

    });
    /*]]>*/
</script>  