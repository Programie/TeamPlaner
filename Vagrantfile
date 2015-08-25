$script = <<SHELL
	aptitude update
	puppet module install puppetlabs-apache
	puppet module install puppetlabs-mysql
	puppet module install tPl0ch-composer
	puppet module install puppetlabs-apt
	puppet module install puppetlabs-nodejs
SHELL

Vagrant.configure(2) do |config|
	config.vm.box = "puppetlabs/debian-7.8-64-puppet"
	config.vm.box_version = "1.0.2"
	config.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true
	config.vm.synced_folder ".", "/opt/teamplaner"
	config.vm.provision "shell",
		inline: $script
	config.vm.provision "puppet" do |puppet|
		puppet.manifests_path = "vagrant/puppet/manifests"
	end
end