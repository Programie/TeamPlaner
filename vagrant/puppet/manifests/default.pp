package {"htop":
  ensure  => "installed",
}

package {"vim":
  ensure  => "installed",
}

package {"php5-mysql":
  ensure  => "installed",
}

package {"php5-curl":
  ensure  => "installed",
}

package {"apt-transport-https":
  ensure  => "installed",
}

user {"www-data":
  groups  => ["vagrant"],
}

file {"/opt/teamplaner/config/config.json":
  source  => "/opt/teamplaner/vagrant/config.json",
}

class { "apache":
  mpm_module    => "prefork",
  default_vhost => false,
  manage_user   => false,
}

apache::vhost {"localhost":
  port      => 80,
  docroot   => "/opt/teamplaner/httpdocs",
  override  => ["All"],
}

include apache::mod::php
include apache::mod::rewrite

class {"::mysql::server":
  remove_default_accounts => true,
}

mysql::db {"teamplaner_db":
  dbname    => "teamplaner",
  user      => "teamplaner",
  password  => "teamplaner",
  host      => "localhost",
  grant     => ["SELECT", "INSERT", "UPDATE", "DELETE"],
  sql       => [
    "/opt/teamplaner/database.sql",
    "/opt/teamplaner/sample_data.sql"
  ],
}

include composer

composer::exec {"composer_install":
  cmd => "install",
  cwd => "/opt/teamplaner",
}

include nodejs

package {"bower":
  ensure    => present,
  provider  => "npm",
  require   => Class["nodejs"],
}

exec {"bower_install":
  path        => ["/bin", "/usr/bin", "/usr/local/bin"],
  cwd         => "/opt/teamplaner/httpdocs",
  user        => "vagrant",
  command     => "bower install --config.interactive=false",
  environment => ["HOME=/home/vagrant"],
  require     => Package["bower"],
}
