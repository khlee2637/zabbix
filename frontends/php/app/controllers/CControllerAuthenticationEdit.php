<?php
/*
** Zabbix
** Copyright (C) 2001-2018 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CControllerAuthenticationEdit extends CController {

	protected function init() {
		$this->disableSIDValidation();
	}

	/**
	 * Validate user input.
	 *
	 * @return bool
	 */
	protected function checkInput() {
		$fields = [
			'form_refresh' => 'string',
			'ldap_test_user' => 'string',
			'ldap_test_password' => 'string',
			'change_bind_password' => 'in 0,1',
			'authentication_type' => 'in '.ZBX_AUTH_INTERNAL.','.ZBX_AUTH_LDAP,
			'login_case_sensitive' => 'in 0,'.ZBX_AUTH_CASE_MATCH,
			'ldap_configured' => 'in 0,'.ZBX_AUTH_LDAP_ENABLED,
			'ldap_host' => 'db config.ldap_host',
			'ldap_port' => 'int32',
			'ldap_base_dn' => 'db config.ldap_base_dn',
			'ldap_bind_dn' => 'db config.ldap_bind_dn',
			'ldap_search_attribute' => 'db config.ldap_search_attribute',
			'ldap_bind_password' => 'db config.ldap_bind_password',
			'http_auth_enabled' => 'in 0,1',
			'http_login_form' => 'in '.ZBX_AUTH_FORM_ZABBIX.','.ZBX_AUTH_FORM_HTTP,
			'http_strip_domains' => 'db config.http_strip_domains'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	/**
	 * Validate is user allowed to change configuration.
	 *
	 * @return bool
	 */
	protected function checkPermissions() {
		return $this->getUserType() == USER_TYPE_SUPER_ADMIN;
	}

	protected function doAction() {
		$ldap_status = (new CFrontendSetup())->checkPhpLdapModule();
		$config = select_config();
		$test_users = [];

		$data = [
			'action_submit' => 'administration.auth.update',
			'action_passw_change' => 'administration.auth.edit',
			'ldap_configured' => $config['ldap_configured'],
			'ldap_error' => ($ldap_status['result'] == CFrontendSetup::CHECK_OK) ? '' : $ldap_status['error'],
			'ldap_test_password' => '',
			'change_bind_password' => 0,
			'authentication_type' => $config['authentication_type'],
			'db_authentication_type' => $config['authentication_type'],
			'login_case_sensitive' => $config['login_case_sensitive'],
			'ldap_host' => $config['ldap_host'],
			'ldap_port' => $config['ldap_port'],
			'ldap_base_dn' => $config['ldap_base_dn'],
			'ldap_bind_dn' => $config['ldap_bind_dn'],
			'ldap_search_attribute' => $config['ldap_search_attribute'],
			'ldap_bind_password' => $config['ldap_bind_password'],
			'ldap_test_user' => '',
			'http_auth_enabled' => $config['http_auth_enabled'],
			'http_login_form' => $config['http_login_form'],
			'http_strip_domains' => $config['http_strip_domains'],
			'form_refresh' => 0,
		];

		$this->getInputs($data, [
			'form_refresh',
			'change_bind_password',
			'authentication_type',
			'login_case_sensitive',
			'ldap_configured',
			'ldap_host',
			'ldap_port',
			'ldap_base_dn',
			'ldap_bind_dn',
			'ldap_search_attribute',
			'ldap_bind_password',
			'ldap_test_user',
			'http_auth_enabled',
			'http_login_form',
			'http_strip_domains'
		]);

		$data['ldap_enabled'] = ($ldap_status['result'] == CFrontendSetup::CHECK_OK
			&& $data['ldap_configured'] == ZBX_AUTH_LDAP_ENABLED);

		if ($data['ldap_test_user'] === '') {
			$data['ldap_test_user'] = CWebUser::$data['alias'];
		}
		$response = new CControllerResponseData($data);
		$response->setTitle(_('Authentication'));
		$this->setResponse($response);
	}
}
