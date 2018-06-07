package { "htop":
  ensure => installed,
}

package { "vim":
  ensure => installed,
}

package { "git":
  ensure => installed,
}

package { "nodejs-legacy":
  ensure => installed,
}

package { "npm":
  ensure => installed,
}

package { "php5-cli":
  ensure => installed,
}

package { "php5-mysql":
  ensure => installed,
}

package { "php5-curl":
  ensure => installed,
}

user { "www-data":
  groups => ["vagrant"],
}

file { "/opt/teamplaner/config/config.json":
  source => "/opt/teamplaner/vagrant/config.json",
}

class { "apache":
  mpm_module    => "prefork",
  default_vhost => false,
  manage_user   => false,
}

apache::vhost { "localhost":
  port     => 80,
  docroot  => "/opt/teamplaner/httpdocs",
  override => ["All"],
}

include apache::mod::php
include apache::mod::rewrite

class { "::mysql::server":
  remove_default_accounts => true,
}

mysql::db { "teamplaner_db":
  dbname   => "teamplaner",
  user     => "teamplaner",
  password => "teamplaner",
  host     => "localhost",
  grant    => ["SELECT", "INSERT", "UPDATE", "DELETE"],
  sql      => [
    "/opt/teamplaner/database.sql",
    "/opt/teamplaner/sample_data.sql"
  ],
}

class { "composer":
  command_name => "composer",
  target_dir   => "/usr/local/bin",
}

exec { "composer_install":
  path        => ["/usr/local/sbin", "/usr/local/bin", "/usr/sbin", "/usr/bin", "/sbin", "/bin"],
  command     => "composer install",
  cwd         => "/opt/teamplaner",
  environment => ["HOME=/home/vagrant"],
  require     => Class["composer"],
}

exec { "npm_install_bower":
  path    => ["/usr/local/sbin", "/usr/local/bin", "/usr/sbin", "/usr/bin", "/sbin", "/bin"],
  command => "npm install -g bower",
  require => Package["nodejs-legacy", "npm"],
}

exec { "bower_install":
  path        => ["/bin", "/usr/bin", "/usr/local/bin"],
  cwd         => "/opt/teamplaner",
  user        => "vagrant",
  command     => "bower install --config.interactive=false",
  environment => ["HOME=/home/vagrant"],
  require     => Exec["npm_install_bower"],
}