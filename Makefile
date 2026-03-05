IMAGE=gmlview

all: build run

build: Dockerfile src/*
	docker build -t $(IMAGE) .

run: build
	docker run -p 80:80 \
		-v $(PWD)/src:/app \
		-v $(PWD)/gml:/app/gml \
		$(IMAGE)
