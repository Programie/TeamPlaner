<?php
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
	 * @return bool true if the user is allowed to access the application, false if not
	 */
	public function checkPermissions();

	/**
	 * Get a list of teams this user is member of.
	 *
	 * @return array|null An array with the names (not title!) of the teams or null if all teams are allowed
	 */
	public function getTeams();

	/**
	 * Get the name of the logged in user.
	 *
	 * @return string The name of the currently logged in user
	 */
	public function getUsername();
}