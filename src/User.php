<?php

namespace rdx\bookr;

class User extends Model {

	static public $_table = 'users';

	static public function fromAuth( $username, $password ) {
		if ( $username && $password ) {
			$user = self::first(['username' => $username]);
			if ( $user && password_verify($password, $user->password) ) {
				if ( $user->last_login < time() - 60 ) {
					$user->update(['last_login' => time()]);
				}
				return $user;
			}
		}
	}

	protected function getSetting( $setting, $default = null ) {
		$settings = $this->settings_array;
		return $settings[$setting] ?? $default;
	}

	protected function get_settings_array() {
		return json_decode($this->settings, true) ?: [];
	}

	protected function get_setting_started() {
		return (bool) $this->getSetting('started', false);
	}

	protected function get_setting_summary() {
		return (bool) $this->getSetting('summary', true);
	}

	protected function get_setting_summary_in_list() {
		return (bool) $this->getSetting('summary_in_list', true) && $this->setting_summary;
	}

	protected function get_setting_notes() {
		return (bool) $this->getSetting('notes', true);
	}

	protected function get_setting_notes_in_list() {
		return (bool) $this->getSetting('notes_in_list', true) && $this->setting_notes;
	}

	protected function get_setting_rating() {
		return (bool) $this->getSetting('rating', false);
	}

	protected function get_setting_labels() {
		return (bool) $this->getSetting('labels', false);
	}

	protected function get_setting_pubyear() {
		return (bool) $this->getSetting('pubyear', true);
	}

}
