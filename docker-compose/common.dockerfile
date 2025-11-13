###########################################
# Install BYBN certificates.
# Works with alpine and debian.
###########################################


RUN <<EOF
set -eux

#######################
# BYBN PROXY CERT
#######################
BYBN_PROXY_CERT="-----BEGIN CERTIFICATE-----
MIIFYDCCA0igAwIBAgIUGvMTy3zkPAwF11iXbrrzrFzRH7wwDQYJKoZIhvcNAQEL
BQAwSDELMAkGA1UEBhMCREUxGTAXBgNVBAoMEEZyZWlzdGFhdCBCYXllcm4xHjAc
BgNVBAMMFUJheWVybiBXZWIgR2F0ZXdheSBDQTAeFw0yMDA5MDQwNjU4MzhaFw00
MDA5MDMwNjU4MzhaMEgxCzAJBgNVBAYTAkRFMRkwFwYDVQQKDBBGcmVpc3RhYXQg
QmF5ZXJuMR4wHAYDVQQDDBVCYXllcm4gV2ViIEdhdGV3YXkgQ0EwggIiMA0GCSqG
SIb3DQEBAQUAA4ICDwAwggIKAoICAQDP6w5bi/1QMJI9peHZlXoYx3LuC2XCGHol
NVn7JvIi3vil+2TTiFSA7G0edcN3GwdPWaB7zQjxDsdgmPdCb/UE901uZ1vU4MK+
zV4+Zp7Cq4wSg5WZy2u4rAD77QagK7J83MlMR9UjLFhE1NJ4iNxbYTv6GS6TwOoh
3N6tODcvcPizthAmd+Br0ktAnuAAsRdfomYMpNRGm/7/IOT/ARPC93mgSUYz6zEQ
90+cFQnfsVuZap/LYz2AS9IGBRccZODfKWiLRx1yqERVhJjFHVyUHjU7xi6nOQhF
0ridz4s7FuiNE0dVnn3M6f3oD9BVcFPilcMaIui/pLFaT4tCQ7Oi53CmejDzC0rm
3zzsz7IR24CKguE1GFmp5bYz4PAkEs434avoVX4l4JadY43d580aIOpeX9JID6Wg
jSAttjWEP81FLfrGqdfMPYRTxHZ8Rykncjdigt0j7478fN3b+anikYSWKBF4nA2y
pxx5885SJJoq2DOS7NBEK1KdS/1aHROseCFlCV4QumZmBa1u5P2iyPNAVlfOnWTH
/nElKU6Pg+FwrJgxKltAjoH2DzwShOo5OatWjO0tcRFbQfXiBl60tSsUH9XvTtBi
B0contHElERn0X1PEoTvhSnIzQNwMiweBBpvUW2tK2dlulSWai0u9mW6x5APrfaF
Sf9TxeJCTQIDAQABo0IwQDAPBgNVHRMBAf8EBTADAQH/MB0GA1UdDgQWBBQHCIDK
WGKfQFRFm6Y6ZRUAVZS3hjAOBgNVHQ8BAf8EBAMCAQYwDQYJKoZIhvcNAQELBQAD
ggIBAK0tRp8hinPYn2NhqSXlN3L5RMA+qU/CGiDRwu8IIyQtCwEJeLRPuajfwPLX
9uqJn4svizvdnLZN/Je0S4w/PbhxmSdB3WgG6fe9SNO/aJjnKkZ0cNe3WLs95PQV
eD0owXrHILwH31CremaaV7vNUpgJpcrvzwhsVCOIkvDS4ofAaSHfKjbGT2N7zg5p
3vgiXDrZZVFUzvUeQTwRX8L2krH8KfYslPccevn/KxcDHpaZz6HyEg6Hw2LB30t5
Fphz9yOOvUW/A1lqqCPKWZ8tM51KE8rs/SjrxKay9iaUfdyRe5zACb6VFDg3OaPr
7xz2bunHw6psp7EJ8rKEE/2I8iZmjfu4THT9aB3GpbTHL7/nUeb91o4X+awzJOME
yYcEQbFDg5M4A4Ffjm9d3fK4spoBhQZhFSmU5p0Fy9aoP5jl8yp1hUHb+7ri+D9c
dPNATG4CFcpMViBlIr6FjNC3gM+5tN0x2L02ED2ZHxcfDOnvFYjDwqIROMRFCXHV
+6mivht3FrUDC+5rLDsix8abd3t9pF+CAmFFz0BTDhJA1CCQvfkRURBlorgBWyTi
IclhB9z5OJyKz4tKOZEw1s3G8ayI+Jk39kgx6D71E7FDoLYmKImMwdEqrXKf3uuj
XHHs7veExP7Lfj4O9pyCP1KpPVz3InaCDR0Q9gu3FYG2apNw
-----END CERTIFICATE-----"

# yes, this works for both alpine and debian:
mkdir -p /usr/local/share/ca-certificates/
echo "$BYBN_PROXY_CERT" > /usr/local/share/ca-certificates/bayernwebgatewayca.crt
# temporarily neccessary for apk add
echo "$BYBN_PROXY_CERT" >> /etc/ssl/certs/ca-certificates.crt


if which apt-get; then
    apt-get update
    apt-get install -y wget ca-certificates
    rm -rf /var/cache/apt/archives /var/lib/apt/lists/*
elif which apk; then
    apk add --no-cache wget
fi


#######################
# BYBN PKI CERTS
#######################
# https://www.pki.bayern.de/vpki/allg/cazert/index.html
certs="https://www.pki.bayern.de/mam/pki/vpki/bayern-root-ca-2019_base64.cer"

# https://www.pki.bayern.de/infrapki/allg/caz/index.html
for i in 2015 2020 2021-nopss; do
    certs="$certs https://www.pki.bayern.de/mam/pki/ipki/root-ca-$i-base64.cer"
done

cd /usr/local/share/ca-certificates/
wget $certs

# Rename all *.cer to *.crt - otherwise update-ca-certificates will skip these files.
for f in *.cer; do
        mv "$f" "${f%.cer}.crt"
done

update-ca-certificates
EOF
