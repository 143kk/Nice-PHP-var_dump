var_dump2
=========

Nice php var_dump library


**Usage:**
```php
<?php

include "NDumper.Class.php";
...

$var1 = 435;
$var2 = "Hello world!";
$varN = array(
  23, 
  34, 
  array(
    '333' => 34324
  )
);

...

vd($var1, $var2, $varN);

?>
```

**Result:**

![Screenshot](https://raw.github.com/golden13/Nice-PHP-var_dump/master/vd_screen1.png)
