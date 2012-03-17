<div id="replot">Replot the chart!</div>
<div id="piechart" style="margin-top:20px; margin-left:20px; width:400px; height:300px;"></div>
<script class="code" type="text/javascript">$(document).ready(function(){ 
    var plot;
    $("#replot").click(function(){
        if (plot){
            plot.destroy();
        }
        var random = Math.random() * 500;
        plot = $.jqplot('piechart', [[['650 Lines of Code', random],['214 Lines of Comment',214],['16 Empty Lines',16]]], {'seriesDefaults':{'renderer':$.jqplot.PieRenderer,'rendererOptions':{'showDataLabels':true}},'grid':{'drawBorder':false,'drawGridlines':false,'background':'transparent','shadow':false},'legend':{'show':'true','location':'e','placement':'inside'}});
    });
});</script>


<?php
/*$this->widget('application.extensions.jqplot.JqplotGraphWidget', array(
    'id' => 'piechart',
    'data' => array(array(
            array('650 Lines of Code', 650),
            array('214 Lines of Comment', 214),
            array('16 Empty Lines', 16)
        )),
    'options' => array(
        'seriesDefaults' => array(
            'renderer' => 'js:$.jqplot.PieRenderer',
            'rendererOptions' => array('showDataLabels' => true)
        ),
        'grid' => array (
            'drawBorder' => false, 
            'drawGridlines' => false,
            'background' => 'transparent',
            'shadow' => false
        ),
        'legend' => array('show' => 'true', 'location' => 'e', 'placement' => 'inside'),
    ),
    'htmlOptions' => array(
        'style' => 'width:600px;height:300px;'
    ),
    'pluginScriptFile' => array(
        'jqplot.pieRenderer.min.js', 'jqplot.donutRenderer.min.js')
        )
);*/
$this->widget('application.extensions.jqplot.JqplotJsWidget', array('pluginScriptFile' => array('jqplot.pieRenderer.min.js')));
?>
