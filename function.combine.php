<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty combine function plugin
 *
 * Type:     function<br>
 * Name:     combine<br>
 * Date:     September 2, 2013
 * Purpose:  Combine content from several js or css files into one
 * Input:    string to count
 * Example:  {count input=$array_of_files_to_combine output=$path_to_output_file age=$seconds_to_try_recombine_file}
 *
 * @author   Tkatchev Philippe <zoonman at gmail dot com>
 * @version 1.0
 * @param array
 * @param string
 * @param int
 * @return string
 */

function smarty_function_combine($params,&$smarty)
{

	/**
	 * Print filename
	 *
	 * @param string $params
	 */
	if (!function_exists('sfc_print_out')) {
		function sfc_print_out($params) {
			$last_mtime = 0;
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$params['cache_file_name'])) {
				$last_mtime = file_get_contents($_SERVER['DOCUMENT_ROOT'].$params['cache_file_name']);
				echo $last_mtime;
			}
			$output_filename = preg_replace("/\.(js|css)$/i", date("_YmdHis.",$last_mtime)."$1", $params['output']);
			echo $output_filename;
		}	
	}	
	
	/**
	 * Build combined file
	 *
	 * @param array $params
	 */
	if (!function_exists('sfc_build_combine')) {
		function sfc_build_combine($params) {
			$filelist = array();
			$lastest_mtime = 0;
			foreach ($params['input'] as $item) {
				
				if (file_exists($_SERVER['DOCUMENT_ROOT'].$item)) {
					$mtime = filemtime($_SERVER['DOCUMENT_ROOT'].$item);
					$lastest_mtime = max($lastest_mtime, $mtime);
					$filelist[] = array('name' => $item, 'time' => $mtime);
				}
				else {
					trigger_error('File '.$_SERVER['DOCUMENT_ROOT'].$item.' does not exists!', E_USER_WARNING);
				}
			}
			$last_cmtime = 0;
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$params['cache_file_name'])) {
				$last_cmtime = file_get_contents($_SERVER['DOCUMENT_ROOT'].$params['cache_file_name']);
			}
			if ($lastest_mtime > $last_cmtime) {
				$glob_mask = preg_replace("/\.(js|css)$/i","_*.$1", $params['output']);
				$files_to_cleanup = glob($_SERVER['DOCUMENT_ROOT'].$glob_mask);
				foreach ($files_to_cleanup as $cfile) {
					if(is_file($_SERVER['DOCUMENT_ROOT'].$cfile) && file_exists($_SERVER['DOCUMENT_ROOT'].$cfile)) @unlink($_SERVER['DOCUMENT_ROOT'].$cfile);
				}
				$output_filename = preg_replace("/\.(js|css)$/i", date("_YmdHis.", $lastest_mtime)."$1", $params['output']);
				$fh = fopen($_SERVER['DOCUMENT_ROOT'].$output_filename, "a+");
				if (flock($fh, LOCK_EX)) {
					foreach ($filelist as $file) {
						fputs($fh, PHP_EOL.PHP_EOL."/* ".$file['name']." @ ".date("c", $file['time'])." */".PHP_EOL.PHP_EOL);
						fputs($fh, file_get_contents($_SERVER['DOCUMENT_ROOT'].$file['name']));
					}
					flock($fh,LOCK_UN);
					file_put_contents($_SERVER['DOCUMENT_ROOT'].$params['cache_file_name'], $lastest_mtime, LOCK_EX);
				}
				fclose($fh);
				clearstatcache();
			}
			touch($_SERVER['DOCUMENT_ROOT'].$params['cache_file_name']);
			sfc_print_out($params);
		}
	}
	if (isset($params['input'])) {
		if(is_array($params['input']) && count($params['input']) > 0) {
			$ext = pathinfo($params['input'][0], PATHINFO_EXTENSION);
			if(in_array($ext,array('js','css'))) {
				$params['type'] = $ext;
				if (!isset($params['output'])) $params['output'] = dirname($params['input'][0]).'/combined.'.$ext;
				if (!isset($params['age'])) $params['age'] = 3600;
				if (!isset($params['cache_file_name'])) $params['cache_file_name'] = $params['output'].'.cache';
				$cache_file_name = $params['cache_file_name'];
				if (file_exists($_SERVER['DOCUMENT_ROOT'].$cache_file_name)) {
					$cache_mtime = filemtime($_SERVER['DOCUMENT_ROOT'].$cache_file_name);
					if ($cache_mtime+$params['age'] < time()) {
						sfc_build_combine($params);
					}
					else {
						sfc_print_out($params);
					}
				}
				else {
					sfc_build_combine($params);
				}
			}
			else {
				trigger_error("input file must have js or css extension", E_USER_NOTICE);
				return;
			}
		}
		else {
			trigger_error("input must be array and have one item at least", E_USER_NOTICE);
			return;
		}
	}
	else {
		trigger_error("input cannot be empty",E_USER_NOTICE);
		return;
	}
}
?>