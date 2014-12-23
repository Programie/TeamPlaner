<?php
namespace userauth;

interface iUserAuth
{
	/**
	 * Check whether the user is logged in.
	 *
	 * @return bool true if the user is logged in, false if not
	 */
	public function checkAuth();

	/**
	 * Force user authentication.
	 */
	public function forceAuth();

	/**
	 * Logout the currently logged in user.
	 */
	public function logout();

	/**
	 * Check whether the user has the required permission.
	 *
	 * @return bool true if the user is allowed to access the application, false if not.
	 */
	public function checkPermissions();

	/**
	 * Get the name of the logged in user.
	 *
	 * @return string The name of the currently logged in user
	 */
	public function getUsername();
}