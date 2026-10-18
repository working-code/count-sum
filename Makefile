test:
	docker compose run --rm --cap-drop=DAC_OVERRIDE --user "$$(id -u):$$(id -g)" application composer test

lint:
	docker compose run --rm --cap-drop=DAC_OVERRIDE --user "$$(id -u):$$(id -g)" application composer lint
