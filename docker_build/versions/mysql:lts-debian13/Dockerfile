FROM dhi.io/mysql:lts-debian13-dev AS builder

# Create certs directory
RUN mkdir -p /opt/mysql/certs


FROM dhi.io/mysql:lts-debian13
COPY --from=builder /opt/mysql/certs /opt/mysql/certs

EXPOSE 3306 33060

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
