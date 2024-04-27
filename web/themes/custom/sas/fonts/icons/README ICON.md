#ICON FONT
This icon font is used for all icons on the website.

##Add new icons
* go to https://icomoon.io/app/#/select
* remove icon-set "IconMoon-free" by pressing ☰ and then "remove set ✖"
* press "+ import icons" and select the selection.json file
*** then press ☰ and "import to set"**
* chose your svg icon
* remove all colors in the SVG if not removed before
* rename icon in english with a simple name
* download zip archive and replace all files in "\themes\custom\sas\fonts\icons"
* rename style.scss file in icons.scss file
* in variables.scss, set $icomoon-font-path to "../../sas/fonts/icons/fonts" !default;
* changed name $icomoon-font-family to $icomoon-font-family-sas
* changed name $icomoon-font-path to $icomoon-font-path-sas

>Take attention that iconmoon doesn't support stroked poligons
>If your SVG file is drawn with stroke poligons you need to retrace it with any vector editor.

##How to set an icon
* Open demo file demo.html and chose your icon 'icon-more' for example
* put the css class on the element you need (<i>);

>example
```sh
<i class="icon-more"></i>
```
