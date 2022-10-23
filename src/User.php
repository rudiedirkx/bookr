<?php

namespace rdx\bookr;

class User extends Model {

	static public $_table = 'users';

	static public function fromAuth( string $username, string $password ) : ?self {
		if ( $username && $password ) {
			$user = self::first(['username' => $username]);
			if ( $user && password_verify($password, $user->password) ) {
				return $user;
			}
		}
		return null;
	}

	static public function fromSession( int $id ) : ?self {
		$user = self::find($id);
		if ( $user ) {
			if ( $user->last_login < time() - 60 ) {
				$user->update(['last_login' => time()]);
			}
			return $user;
		}
		return null;
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

	protected function get_setting_pages() {
		return (bool) $this->getSetting('pages', false);
	}

	protected function get_setting_pages_in_list() {
		return (bool) $this->getSetting('pages_in_list', false);
	}

}
