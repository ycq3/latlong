<?php


namespace Encore\Admin\Latlong\Map;


class Here extends AbstractMap
{
    protected $key = '';
    protected $api = '-----------';
    public function __construct($key = '')
    {
        $this->key = $key;
    }

    public function getAssets()
    {
        return [
            '//js.api.here.com/v3/3.1/mapsjs-core.js',
            '//js.api.here.com/v3/3.1/mapsjs-service.js',
            '//js.api.here.com/v3/3.1/mapsjs-mapevents.js',
            '//js.api.here.com/v3/3.1/mapsjs-ui.js',
            '//unpkg.com/gcoord@0.2.3/dist/gcoord.js'
        ];
    }

    public function applyScript(array $id)
    {
        return <<<EOT

        (function() {
            function init(name) {
                 var platform  = new H.service.Platform({
                        'apikey': '{$this->key}'
                 });
                var defaultLayers = platform.createDefaultLayers();
        
                var container = document.getElementById("map_"+name);
                
                var lat = $('#{$id['lat']}');
                var lng = $('#{$id['lng']}');
                var result = gcoord.transform(
                    [lat.val(), lng.val()],    // 经纬度坐标
                    gcoord.WGS84,
                    gcoord.BD09,
                );
                
                var map = new H.Map(
                    container,
                    defaultLayers.vector.normal.map,
                    {
                      zoom: 10,
                      center: { lat: result[0], lng: result[1] }
                    }
                );
                
                var behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(map));
                
                var ui = H.ui.UI.createDefault(map, defaultLayers);
                //拖动标记处理
                var marker = new H.map.Marker({lat: lat.val(), lng: lng.val()}, {
                    volatility: true
                });
                marker.draggable = true;
                map.addObject(marker);
                map.addEventListener('dragstart', function(ev) {
                    var target = ev.target,
                    pointer = ev.currentPointer;
                    if (target instanceof H.map.Marker) {
                      var targetPosition = map.geoToScreen(target.getGeometry());
                      target['offset'] = new H.math.Point(pointer.viewportX - targetPosition.x, pointer.viewportY - targetPosition.y);
                      behavior.disable();
                    }
                }, false);
                map.addEventListener('dragend', function(ev) {
                    var target = ev.target;
                    if (target instanceof H.map.Marker) {
                        behavior.enable();
                    }
                }, false);
                map.addEventListener('drag', function(ev) {
                    var target = ev.target,
                    pointer = ev.currentPointer;
                    if (target instanceof H.map.Marker) {
                    console.log(pointer.viewportX,pointer.viewportX-target['offset'].x,target['offset'].x)
                        let x = pointer.viewportX - target['offset'].x;
                        let y = pointer.viewportY - target['offset'].y
                        var result = gcoord.transform(
                            [marker.getGeometry().lat, marker.getGeometry().lng],    // 经纬度坐标
                            gcoord.GCJ02,           // 当前坐标系
                            gcoord.WGS84,           // 目标坐标系
                        );
                        lat.val(result[0]);
                        lng.val(result[1]);
                        
                        lat.val(marker.getGeometry().lat);
                        lng.val(marker.getGeometry().lng);
                        
                        target.setGeometry(map.screenToGeo(x,y));
                    }
                }, false);
                
                map.addEventListener('tap', function(evt) {
                    //获取当前点击地图相对于地图显示界面的坐标
                    var x = evt.currentPointer.viewportX;
                    var y = evt.currentPointer.viewportY;
                    var position = map.screenToGeo (x,y);
                    marker.setGeometry(position);
                    var result = gcoord.transform(
                        [marker.getGeometry().lat, marker.getGeometry().lng],    // 经纬度坐标
                        gcoord.GCJ02,           // 当前坐标系
                        gcoord.WGS84,           // 目标坐标系
                    );
                    lat.val(result[0]);
                    lng.val(result[1]);
                });
            }
            init('{$id['lat']}{$id['lng']}');
        })();
       
EOT;

    }
}
