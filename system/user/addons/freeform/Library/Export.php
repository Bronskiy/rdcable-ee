<?php

namespace Solspace\Addons\Freeform\Library;
use Solspace\Addons\Freeform\Library\AddonBuilder;
use Solspace\Addons\Freeform\Library\Cacher;

class Export extends AddonBuilder
{
	public $cache_path;
	public $export_chunk_size	= 100;
	public $format_dates		= TRUE;

	// --------------------------------------------------------------------

	/**
	 * Main Export function for returning or forcing a download of file
	 *
	 * @access public
	 * @param  array  	$options various download options. Form_id and query required
	 * @return string 	string of file contents or exit on array
	 */

	public function export ($options = array())
	{
		// -------------------------------------
		//	run defaults
		// -------------------------------------

		$defaults = array(
			'method'			=> 'csv',
			'form_id'			=> 0,
			'form_name'			=> '',
			'output'			=> 'string',
			'rows'				=> array(),
			'model'				=> NULL,
			'fields'			=> '*',
			'remove_entry_id'	=> FALSE,
			'header_labels'		=> array(),
			'total_entries'		=> 0
		);

		foreach ($defaults as $key => $value)
		{
			if (isset($options[$key]))
			{
				$defaults[$key] = $options[$key];
			}
		}

		extract($defaults);

		unset($defaults, $options);

		$chunk			= FALSE;
		$chunk_start	= FALSE;
		$chunk_end		= FALSE;

		// -------------------------------------
		//	check prelim data
		// -------------------------------------

		if ( ((! is_array($rows) OR empty($rows)) AND ! $model) OR
			 ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		// -------------------------------------
		//	cache clean
		// -------------------------------------

		$this->clean_file_cache();

		// -------------------------------------
		//	export
		// -------------------------------------

		$method = is_callable(array($this, $method)) ? $method : 'csv';

		//return a string to write somewhere?
		if ($output == 'string')
		{
			if (empty($rows) AND $model)
			{
				$rows = $model->get();
			}

			return $this->$method(array(
				'form_id'			=> $form_id,
				'rows'				=> $rows,
				'remove_entry_id'	=> $remove_entry_id,
				'header_labels'		=> $header_labels,
				'chunk'				=> $chunk,
				'chunk_start'		=> $chunk_start,
				'chunk_end'			=> $chunk_end
			));
		}
		//force a file download
		else
		{
			$form_name = rtrim(trim($form_name), '_') . '_';

			$filename = ee()->security->sanitize_filename(
				'freeform_' . $form_name . 'export_' .
				date('Ymd_Hi_s', ee()->localize->now) . '.' .
				$method
			);

			//do we need to chunk this?
			if (empty($rows) AND $model AND $total_entries > $this->export_chunk_size)
			{
				//Set the execution time to infinite.
				set_time_limit(0);

				//detect chunk size
				$chunk_size = ceil($total_entries/$this->export_chunk_size);

				// -------------------------------------
				//	get file path and check writability
				// -------------------------------------

				ee()->load->helper('file');

				$filepath = $this->cache_file_path($filename);

				//blank
				write_file($filepath, '');

				//chunk and write
				for ($i = 0; $i < $chunk_size; $i++)
				{
					$model->limit($this->export_chunk_size, $i * $this->export_chunk_size);

					$data = $this->$method(array(
						'form_id'			=> $form_id,
						'remove_entry_id'	=> $remove_entry_id,
						'header_labels'		=> $header_labels,
						'rows'				=> $model->get(array(), FALSE),
						'chunk'				=> TRUE,
						'chunk_start'		=> $i == 0,
						'chunk_end'			=> $i == ($chunk_size - 1)
					));

					write_file($filepath, $data, 'a+');

					unset($data);

					Cacher::clear();
				}

				// -------------------------------------
				//	send to user
				// -------------------------------------

				header('Content-disposition: attachment; filename=' . $filename);
				if (version_compare($this->ee_version, '2.10.0', '>='))
				{
					ee()->load->library('mime_type');
					header('Content-type: ' . ee()->mime_type->ofFile($filepath));
				}
				else
				{
					header('Content-type: ' . get_mime_by_extension($filename));
				}
				readfile($filepath);
				exit();
			}
			else
			{
				if (empty($rows) AND $model)
				{
					$rows = $model->get();
				}

				$data = $this->$method(array(
					'form_id'			=> $form_id,
					'rows'				=> $rows,
					'remove_entry_id'	=> $remove_entry_id,
					'header_labels'		=> $header_labels,
					'chunk'				=> $chunk,
					'chunk_start'		=> $chunk_start,
					'chunk_end'			=> $chunk_end
				));

				if ($data)
				{
					ee()->load->helper('download');
					force_download($filename, $data);

					exit();
				}
			}

			//fail safe
			$this->lib('Utils')->full_stop(lang('error_creating_export'));
		}
	}
	//END export


	// --------------------------------------------------------------------

	/**
	 * Find cache file path
	 *
	 * @access public
	 * @param  string $filename optional file name to append
	 * @return string           cache path +
	 */

	public function cache_file_path ($filename = '')
	{
		if ( ! isset($this->cache_path))
		{
			$cache_path = ee()->config->item('cache_path');

			if (empty($cache_path))
			{
				$cache_path = PATH_CACHE;
			}

			$cache_path = rtrim($cache_path, '/') . '/' . $this->lower_name . '/';

			$this->cache_path = $cache_path;

			if ( ! is_dir($cache_path))
			{
				mkdir($cache_path);
			}
		}

		return $this->cache_path . $filename;
	}
	//END cache_file_path


	// --------------------------------------------------------------------

	/**
	 * Cleans items in cache folder older than 3 hours old
	 *
	 * @access public
	 * @return null
	 */

	public function clean_file_cache ()
	{
		ee()->load->helper('directory');

		$cache_path = $this->cache_file_path();

		$files = directory_map($cache_path, 1);

		foreach ($files as $file)
		{
			$file_loc = realpath($cache_path . $file);

			if (is_file($file_loc) AND filemtime($file_loc) < (time() - 7200))
			{
				@unlink($file_loc);
			}
		}
	}
	//END clean_file_cache


	// --------------------------------------------------------------------

	/**
	 * Generate CSV from a query result object
	 *
	 * @access	public
	 * @param	array	Any preferences
	 * @return	string
	 */

	function csv ($options = array())
	{
		$defaults = array(
			'form_id'			=> 0,
			'rows'				=> NULL,
			'remove_entry_id'	=> FALSE,
			'header_labels'		=> array(),
			'delim'				=> ",",
			'newline'			=> "\n",
			'enclosure'			=> '"',
			'chunk'				=> FALSE,
			'chunk_start'		=> FALSE,
			'chunk_end'			=> FALSE,
		);

		foreach ($defaults as $key => $value)
		{
			if (isset($options[$key]))
			{
				$defaults[$key] = $options[$key];
			}
		}

		extract($defaults);

		unset($defaults, $options);

		$out	= '';
		$first	= ( ! $chunk OR ($chunk AND $chunk_start));

		// -------------------------------------
		//	build labels
		// -------------------------------------

		if ($first)
		{
			// First generate the headings from the table column names
			foreach ($rows[0] as $key => $val)
			{
				if (($remove_entry_id AND $key == 'entry_id') OR
					$key == 'form_id')
				{
					continue;
				}

				$label_item = isset($header_labels[$key]) ?
								$header_labels[$key] : '';

				$out .= $enclosure .
						str_replace(
							$enclosure,
							$enclosure . $enclosure,
							$label_item
						) .
						$enclosure .
						$delim;
			}

			$out = rtrim(rtrim($out), $delim);

			$out .= $newline;
		}

		// -------------------------------------
		//	rows
		// -------------------------------------

		foreach ($rows as $row)
		{
			$output_parse = $this->lib('Fields')->apply_field_method(array(
				'method'			=> 'export',
				'form_id'			=> $form_id,
				'field_input_data'	=> $row,
				'export_type'		=> 'csv'
			));

			$row = array_merge($row, $output_parse['variables']);

			$row = $this->parse_dates($row);

			if ($remove_entry_id)
			{
				unset($row['entry_id']);
			}

			unset($row['form_id']);

			foreach ($row as $item)
			{
				$out .= $enclosure .
						str_replace(
							$enclosure,
							$enclosure . $enclosure,
							$item
						) .
						$enclosure .
						$delim;
			}
			$out = rtrim(rtrim($out), $delim);
			$out .= $newline;
		}

		// -------------------------------------
		//	output
		// -------------------------------------

		return $out;
	}
	//END csv


	// --------------------------------------------------------------------

	/**
	 * Generate text from a query result object
	 *
	 * @access	public
	 * @param	array	Any preferences
	 * @return	string
	 */

	public function txt ($options = array())
	{
		return $this->csv(array_merge($options, array(
			'delim'		=> "\t",
			'enclosure'	=> '',
		)));
	}
	//END txt

	


	// --------------------------------------------------------------------

	/**
	 * Parse and convert Dates
	 *
	 * @access	public
	 * @param	array	$row	row array input
	 * @return	array			row array output with dates fixed
	 */

	public function parse_dates($row = array())
	{
		if ( ! $this->format_dates)
		{
			return $row;
		}

		if (isset($row['entry_date']) && $this->is_positive_intlike($row['entry_date']))
		{
			$row['entry_date'] = $this->lib('Utils')->format_cp_date($row['entry_date']);
		}

		if (isset($row['edit_date']) && $this->is_positive_intlike($row['edit_date']))
		{
			$row['edit_date'] = $this->lib('Utils')->format_cp_date($row['edit_date']);
		}

		return $row;
	}
	//END parse_dates
}
//END Export
