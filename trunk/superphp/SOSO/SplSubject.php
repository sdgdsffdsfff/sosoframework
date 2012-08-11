<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.1 2008-12-18
 */
if(!interface_exists('SplSubject')) {
	interface SplSubject{
		function attach(SplObserver $observer);
		function detach(SplObserver $observer);
		function notify();
	}
}