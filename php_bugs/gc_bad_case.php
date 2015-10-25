<?php

/*
PHP use reference count to manager object life. And have GC for cyclic	reference

Every function call or unset() will decrease ref count
for ZValue such as ZObject and ZArray, which will check if it's root in ref linked list
this will happen during ref decreasement

But Zend engine set root buffer max default to 10000
So if you just create more than 10000 ZValue and later cause ref decrease
This will force Zend engine do deep-first mark-and-swap each time your ref decrease

See the `zend_gc.c` line 190 - 242
some logic:
```c
if (!GC_G(gc_enabled)) {
	return;
}
zv->refcount__gc++;
gc_collect_cycles(TSRMLS_C);	//Huge consume
zv->refcount__gc--;
```

So sometime we will meet bad case of GC, see the example
*/

gc_enable();	//This will control whether to use GC for cyclic reference

function doSomething($value) {
	//Do anything. Remember end of scrope will decrease ref count
}

$array = array();
$start = microtime(true);	//time counter
for ($i = 0;$i < 100000; $i++) {	//first generate more than 10000 objects reference
	$array[] = new stdClass();	//just use base object generate reference
}
foreach ($array as $value) {	//call function to decrease ref count
	doSomething($value);
}
var_dump(microtime(true) - $start);

gc_disable();

$array = array();
$start = microtime(true);
for ($i = 0;$i < 100000; $i++) {
	$array[] = new stdClass();
}
foreach ($array as $value) {
	doSomething($value);
}
var_dump(microtime(true) - $start);

?>