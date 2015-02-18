<?php
class TeamHelper
{
	public static function getTeams(PDO $pdo, $teams)
	{
		$data = array();

		if ($teams === null)
		{
			$query = $pdo->query("SELECT `name`, `title` FROM `teams`");
			while ($row = $query->fetch())
			{
				$data[] = $row;
			}
		}
		else
		{
			$query = $pdo->prepare("
				SELECT `name`, `title`
				FROM `teams`
				WHERE `name` = :name
			");

			foreach ($teams as $team)
			{
				$query->execute(array
				(
					":name" => $team
				));

				if (!$query->rowCount())
				{
					continue;
				}

				$data[] = $query->fetch();
			}
		}

		return $data;
	}

	public static function getTeamIdIfAllowed(PDO $pdo, $team, $teams)
	{
		if ($teams !== null and !in_array($team, $teams))
		{
			return null;
		}

		$query = $pdo->prepare("
			SELECT `id`
			FROM `teams`
			WHERE `name` = :name
		");

		$query->execute(array
		(
			":name" => $team
		));

		return $query->fetch()->id;
	}
}