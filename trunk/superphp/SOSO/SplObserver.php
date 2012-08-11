<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.1 2008-12-18
 */
if(!interface_exists('SplObserver')) {
	interface SplObserver{
		function update(SplSubject $subject);
	}
}