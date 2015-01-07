# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  
  config.vm.box = "hashicorp/precise32"

  config.vm.network :private_network, ip: "192.168.33.3"
  config.vm.network :forwarded_port, guest: 80, host: 8080

  config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "vagrant/puppet/manifests"
    puppet.module_path = "vagrant/puppet/modules"
    puppet.manifest_file  = "init.pp"
    puppet.options="--verbose --debug"
  end
end
