require 'yaml'

confDir = File.expand_path("vendor/laravel/homestead", File.dirname(__FILE__))
homesteadYamlPath = File.expand_path("Homestead.yaml", File.dirname(__FILE__))

require File.expand_path(confDir + '/scripts/homestead.rb')

Vagrant.require_version '>= 1.9.0'

Vagrant.configure("2") do |config|
	settings = YAML::load(File.read(homesteadYamlPath))

	Homestead.configure(config, settings)

	# Download and setup our WP site; wp-cli provided out of the box.
	config.vm.provision "shell",
		inline: "wp core download && wp config create",
		privileged: false

	if defined? VagrantPlugins::HostsUpdater
		config.hostsupdater.aliases = settings['sites'].map { |site| site['map'] }
	end
end
