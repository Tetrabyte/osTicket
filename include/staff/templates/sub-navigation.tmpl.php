<?php
if (!$nav || !($subnav=$nav->getSubMenu()) || !is_array($subnav))
    return;

$activeMenu=$nav->getActiveMenu();
if ($activeMenu>0 && !isset($subnav[$activeMenu-1]))
    $activeMenu=0;

$info = $nav->getSubNavInfo();
?>
<nav class="<?php echo @$info['class']; ?> noselect" id="<?php echo $info['id']; ?>" 
 style="<?php if ($_COOKIE['subnav_width']) {
			echo 'width:'.$_COOKIE['subnav_width'].'px;'; } ?>">

<div id="nav_resizer"></div>
<div id="nav_resizer_reset" title="Reset sidebar width"><i class="icon-refresh"></i></div>
	<ul id="sub_nav">
<?php
    foreach($subnav as $k=> $item) {
        if (is_callable($item)) {
            if ($item($nav) && !$activeMenu)
                $activeMenu = 'x';
            continue;
        }
        if(isset($item['droponly'])) continue;
        $class=$item['iconclass'];
        if ($activeMenu && $k+1==$activeMenu
                or (!$activeMenu
                    && (strpos(strtoupper($item['href']),strtoupper(basename($_SERVER['SCRIPT_NAME']))) !== false
                        or ($item['urls']
                            && in_array(basename($_SERVER['SCRIPT_NAME']),$item['urls'])
                            )
                        )))
            $class="$class active";
        if (!($id=$item['id']))
            $id="subnav$k";

        //Extra attributes
        $attr = '';
        if (isset($item['attr']))
            foreach ($item['attr'] as $name => $value)
                $attr.=  sprintf("%s='%s' ", $name, $value);

		echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s" %s><span>%s</span></a></li>',
				$class, $item['href'], $item['title'], $id, $attr, $item['desc']);
		}
?>
	</ul>
</nav>
