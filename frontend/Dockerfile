Dockerfile
FROM node:22-alpine
LABEL "language"="nodejs" 
LABEL "framework"="nuxt.js"

ENV NODE_ENV=production
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000
ENV NUXT_TELEMETRY_DISABLED=1

WORKDIR /src

# Install system dependencies
RUN apk add --no-cache python3 make g++

# Copy package files
COPY Project/finance0810-develop/frontend/package*.json ./

# Install all dependencies
RUN npm install

# Copy source code
COPY Project/finance0810-develop/frontend/ .

# Prepare and build
RUN npm run postinstall
RUN npm run build

EXPOSE 3000

# Use the correct Zeabur output path
CMD ["node", ".zeabur/output/functions/__nitro.func/index.mjs"]