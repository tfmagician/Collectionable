<?php

class OptionsBehavior extends ModelBehavior {
	
	var $settings = array();
	var $defaultSettings = array(
		'setupProperty' => true,
		'defaultOption' => false,
		'optionName' => 'options',
	);

	var $defaultQuery = array(
		'conditions' => null, 'fields' => null, 'joins' => array(), 'limit' => null,
		'offset' => null, 'order' => null, 'page' => null, 'group' => null, 'callbacks' => true
	);

	function setup(&$Model, $settings = array()) {
		$this->settings = array_merge($this->defaultSettings, (array)$settings);
		$optionName = $this->settings['optionName'];
		if ($this->settings['setupProperty']) {
			if (empty($Model->{$optionName})) {
				$Model->{$optionName} = array();
			}
			if (empty($Model->defaultOption)) {
				$Model->defaultOption = $this->settings['defaultOption'];
			}
		}
		return true;
	}

	function beforeFind(&$Model, $query = array()) {
		if (isset($query['options'])) {
			$options = $query['options'];
			unset($query['options']);

			$query = Set::merge($this->defaultQuery, $this->options($Model, $options), Set::filter($query));
		}
		return $query;
	}

	function options(&$Model, $type = null){
		$args = func_get_args();
		if (func_num_args() > 2) {
			array_shift($args);
			$type = $args;
		}

		$option = array();
		if (is_array($type)) {
			foreach ($type as $t) {
				$option = Set::merge($option, $this->options($Model, $t));
			}
		} else {
			$optionName = $this->settings['optionName'];
			$option = isset($Model->{$optionName}[$type]) ? $Model->{$optionName}[$type] : array();
			$default = array();
			if ($Model->defaultOption) {
				$default = $this->_getDefault($Model->defaultOption, $Model->{$optionName});
			}
			$options = array();
			if (isset($option[$optionName]) && !empty($option[$optionName])) {
				$options = $this->_intelligentlyMerge(array(), $option[$optionName], $Model->{$optionName});
				unset($option['options']);
			}
			$option = Set::merge($default, $options, $option);
		}
		return $option;
	}

	function _getDefault($defaultOption, $options) {
		$default = array();
		if ($defaultOption === true && !empty($options['default'])) {
			$default = $options['default'];
		} elseif (is_array($defaultOption)) {
			$default = $this->_intelligentlyMerge($default, $defaultOption, $options);
		} elseif (!empty($options[$defaultOption])) {
			$default = $this->_intelligentlyMerge($default, $options[$defaultOption], $options);
		}
		return $default;
	}

	function _intelligentlyMerge($data, $merges, $options) {
		$merges = (array)$merges;
		if (Set::numeric(array_keys($merges))) {
			foreach($merges as $merge) {
				if (!empty($options[$merge])) {
					$data = $this->_intelligentlyMerge($data, $options[$merge], $options);
				}
			}
		} else {
			$optionName = $this->settings['optionName'];
			if (array_key_exists($optionName, $merges)) {
				$data = $this->_intelligentlyMerge($data, $merges[$optionName], $options);
				unset($merges[$optionName]);
			}
			$data = Set::merge($data, $merges);
		}
		return $data;
	}
}