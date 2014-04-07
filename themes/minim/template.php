<?php

print '<h1>HELLLOOOOOOO</h1>';
function minim_preprocess_html(&$vars) {
  print 'HELLLLLOOOO';
  print '<pre>';
  print_r($vars);
  print '</pre>';
}
