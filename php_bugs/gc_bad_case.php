<?php
/*
PHP use reference count to manager object life. And have GC for cyclic reference

Every end of scope (such as function call) or unset() will decrease ref count
for ZValue such as ZObject and ZArray, which will check if it's root in ref linked list
this will happen during ref decreasement

But Zend engine set root buffer max default to 10000
So if you just create more than 10000 ZValue and later cause ref decrease
This will force Zend engine do deepth-first mark-and-swap each time your ref decrease

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
	//Do anything. Remember end of scope will decrease ref count
}

$array = array();
$start = microtime(true);	//Time counter
for ($i = 0;$i < 100000; $i++) {	//First generate more than 10000 objects
	$array[] = new stdClass();	//Just new stdClass(which is php base object) to generate references
}
foreach ($array as $value) {	//Call function to decrease ref count
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

/*Result:
gc_enable: float(0.093602895736694)
gc_disable: float(0.054068088531494)
*/
?>