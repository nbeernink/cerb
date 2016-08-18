# -*- mode: ruby -*-
# vi: set ft=ruby :

require_relative './provisioning/vagrant/key_authorization.rb'

Vagrant.configure('2') do |config|
  config.vm.box = 'centos/7'
  authorize_key_for_root config, '~/.ssh/id_rsa.pub'
  config.vm.synced_folder ".", "/home/vagrant/sync", disabled: true

  {
    'cerb'   => '192.168.33.20'
  }.each do |short_name, ip|
    config.vm.define short_name do |host|
      host.vm.network 'private_network', ip: ip
      host.vm.hostname = "#{short_name}.dev"
    end

    # Ansible provisioner
    config.vm.provision "ansible" do |ansible|
      ansible.playbook       = "provisioning/playbook.yml"
      ansible.sudo           = true
    end

  end

end
