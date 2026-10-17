# The bucket, the region and the lock of an instance come from terraform init -backend-config, the key is
# <app>/<instance>.tfstate: the values of an installation stay out of the repository (ADR 0059)
terraform {
  backend "s3" {}
}
