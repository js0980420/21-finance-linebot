# --- Builder Stage ---
FROM node:20-alpine as builder

WORKDIR /app

# Install necessary dependencies for native bindings (only in builder stage)
RUN apk add --no-cache python3 make g++

# Copy package.json and lock file first to leverage Docker cache
COPY package*.json ./

# Install all dependencies (dev and prod)
RUN npm install --frozen-lockfile || npm install

# Copy all application source code
COPY . .

# Build the Nuxt.js application for production
RUN npm run build:prod

# --- Production Stage ---
FROM node:20-alpine as production

WORKDIR /app

# Set production environment variables
ENV NODE_ENV=production

# Copy only the built output and production node_modules from the builder stage
COPY --from=builder /app/.output ./output
COPY --from=builder /app/node_modules ./node_modules
# 如果有其他運行時需要的靜態檔案或配置，也需要從 builder 複製過來
# 例如：COPY --from=builder /app/public ./public

EXPOSE 3000

# Command to start the Nuxt.js production server
CMD ["node", "./output/server/index.mjs"]