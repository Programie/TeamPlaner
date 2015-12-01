$script = <<SHELL
	aptitude update
	puppet module install puppetlabs-apache
	puppet module install puppetlabs-mysql
	puppet module install willdurand-composer
SHELL

Vagrant.configure(2) do |config|
	config.vm.box = "dhoppe/debian-8.2.0-amd64"
	config.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true
	config.vm.synced_folder ".", "/opt/teamplaner"
	config.vm.provision "shell",
		inline: $script
	config.vm.provision "puppet" do |puppet|
		puppet.environment_path = "vagrant/puppet"
		puppet.environment = "test"
	end
end