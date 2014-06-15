Smarty plugins
==============

My smarty plugins.
More details available here 
http://www.zoonman.com/projects/smarty-plugins/


Combine
=======

Combine plugin allows concatenating several js or css files into one. 
It can be useful for big projects with a lot of several small CSS and JS files.
Usage example for Smarty 3

    <script type="text/javascript" 
    src="/{combine input=array('/js/core.js','/js/slideviewer.js') output='/js/big.js' age='30'}" 
    charset="utf-8"></script>


You should be sure that js folder is writable to web-server daemon (your php script).


