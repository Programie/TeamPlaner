$packages = [
  "apt-transport-https",
  "ca-certificates",
  "git",
  "htop",
  "lsb-release",
  "vim",
]

package { $packages: }

apt::source { "packages.sury.org_php":
  location => "https://packages.sury.org/php",
  release  => "stretch",
  repos    => "main",
  key      => {
    id     => "DF3D585DB8F0EB658690A554AC0E47584A7A714D",
    source => "https://packages.sury.org/php/apt.gpg",
  },
  require  => Package["apt-transport-https", "ca-certificates"],
}

apt::pin { "packages.sury.org_php":
  priority   => 1000,
  originator => "deb.sury.org",
  require    => Apt::Source["packages.sury.org_php"],
}

$php_modules = [
  "cli",
  "curl",
  "mysql",
]

$php_modules.each | $module | {
  package { "php7.2-${module}":
    require => [
      Apt::Source["packages.sury.org_php"],
      Apt::Pin["packages.sury.org_php"],
      Class["apt::update"],
    ],
    notify  => Class["apache::service"],
  }
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

package { "libapache2-mod-php7.2":
  require => [
    Class["apache"],
    Apt::Source["packages.sury.org_php"],
    Apt::Pin["packages.sury.org_php"],
    Class["apt::update"],
  ],
}

class { "apache::mod::php":
  php_version => "7.2",
}
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
  require      => Package["php7.2-cli"],
}

exec { "composer_install":
  path        => ["/usr/local/sbin", "/usr/local/bin", "/usr/sbin", "/usr/bin", "/sbin", "/bin"],
  command     => "composer install",
  cwd         => "/opt/teamplaner",
  user        => "vagrant",
  environment => ["HOME=/home/vagrant"],
  require     => Class["composer"],
}

class { "nodejs":
  repo_url_suffix => "9.x",
}

package { "bower":
  provider => "npm",
  require  => Class["nodejs"],
}

exec { "bower_install":
  path        => ["/bin", "/usr/bin", "/usr/local/bin"],
  cwd         => "/opt/teamplaner",
  user        => "vagrant",
  command     => "bower install --config.interactive=false",
  environment => ["HOME=/home/vagrant"],
  require     => Package["bower"],
}