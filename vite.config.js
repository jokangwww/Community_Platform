import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/buddy-programme/main.tsx',
                'resources/js/forum/main.tsx',
                'resources/js/poll-petition/main.tsx',
                'resources/js/forum-polls/admin-main.tsx'
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js/buddy-programme'),
            'figma:asset/dc75ddcb82ce7894317d64b2cada15cb464e9883.png': path.resolve(__dirname, './resources/js/forum-polls/assets/dc75ddcb82ce7894317d64b2cada15cb464e9883.png'),
            'figma:asset/cde431304912e0041ba8f68a00fa4a317d119658.png': path.resolve(__dirname, './resources/js/forum-polls/assets/cde431304912e0041ba8f68a00fa4a317d119658.png'),
            'figma:asset/c1f9c116b612341fa3e53f5eb1ce0440892bfc99.png': path.resolve(__dirname, './resources/js/forum-polls/assets/c1f9c116b612341fa3e53f5eb1ce0440892bfc99.png'),
            'figma:asset/b84e373f5ef1b3583ea25a5b8f00a4cfca24c3b2.png': path.resolve(__dirname, './resources/js/forum-polls/assets/b84e373f5ef1b3583ea25a5b8f00a4cfca24c3b2.png'),
            'figma:asset/b506f01bd5f60dcffd00cfa1efcf9c377bcb4ecb.png': path.resolve(__dirname, './resources/js/forum-polls/assets/b506f01bd5f60dcffd00cfa1efcf9c377bcb4ecb.png'),
            'figma:asset/b1e5cf147d6c9e90a9f51f2786d3dbabc26515a9.png': path.resolve(__dirname, './resources/js/forum-polls/assets/b1e5cf147d6c9e90a9f51f2786d3dbabc26515a9.png'),
            'figma:asset/ac165a0692dd5ba835d9aabd416b9a000bf20280.png': path.resolve(__dirname, './resources/js/forum-polls/assets/ac165a0692dd5ba835d9aabd416b9a000bf20280.png'),
            'figma:asset/a4deb6a1f59de2b0ffba2030505c8621c1ec5507.png': path.resolve(__dirname, './resources/js/forum-polls/assets/a4deb6a1f59de2b0ffba2030505c8621c1ec5507.png'),
            'figma:asset/92986094ea3a2d185feb004592a781e393f7ec26.png': path.resolve(__dirname, './resources/js/forum-polls/assets/92986094ea3a2d185feb004592a781e393f7ec26.png'),
            'figma:asset/7983d9853869cba096a089efdf728cbb30c67856.png': path.resolve(__dirname, './resources/js/forum-polls/assets/7983d9853869cba096a089efdf728cbb30c67856.png'),
            'figma:asset/6d75fbc98d70c950db6e104403b2385f598d13be.png': path.resolve(__dirname, './resources/js/forum-polls/assets/6d75fbc98d70c950db6e104403b2385f598d13be.png'),
            'figma:asset/61554c3794e4c97ca51ac5862ec2e8ffae4d1f7e.png': path.resolve(__dirname, './resources/js/forum-polls/assets/61554c3794e4c97ca51ac5862ec2e8ffae4d1f7e.png'),
            'figma:asset/3183a55130187e8b682cc4a49796c5f5f33e0709.png': path.resolve(__dirname, './resources/js/forum-polls/assets/3183a55130187e8b682cc4a49796c5f5f33e0709.png'),
            'figma:asset/2eee02f8a6fd0ff33cf63075b24892e6ad950908.png': path.resolve(__dirname, './resources/js/forum-polls/assets/2eee02f8a6fd0ff33cf63075b24892e6ad950908.png'),
            'figma:asset/230810bfb52cccd2a62dd90feea52c20b94d4d5c.png': path.resolve(__dirname, './resources/js/forum-polls/assets/230810bfb52cccd2a62dd90feea52c20b94d4d5c.png'),
            'figma:asset/155c9a8d5265d92c778710564e52ec0f9d4bd5dc.png': path.resolve(__dirname, './resources/js/forum-polls/assets/155c9a8d5265d92c778710564e52ec0f9d4bd5dc.png'),
            'figma:asset/12ed6e00b03bd9d47420e0808a14d3533368a838.png': path.resolve(__dirname, './resources/js/forum-polls/assets/12ed6e00b03bd9d47420e0808a14d3533368a838.png'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        chunkSizeWarningLimit: 1500,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-react': ['react', 'react-dom'],
                    'vendor-radix': [
                        '@radix-ui/react-dialog',
                        '@radix-ui/react-dropdown-menu',
                        '@radix-ui/react-select',
                        '@radix-ui/react-tabs',
                        '@radix-ui/react-tooltip',
                        '@radix-ui/react-popover',
                        '@radix-ui/react-accordion',
                        '@radix-ui/react-checkbox',
                        '@radix-ui/react-radio-group',
                        '@radix-ui/react-switch',
                        '@radix-ui/react-avatar',
                        '@radix-ui/react-progress',
                        '@radix-ui/react-scroll-area',
                        '@radix-ui/react-separator',
                        '@radix-ui/react-slot',
                        '@radix-ui/react-label',
                    ],
                    'vendor-charts': ['recharts'],
                    'vendor-icons': ['lucide-react'],
                    'vendor-forms': ['react-hook-form', '@hookform/resolvers', 'zod'],
                    'vendor-utils': ['clsx', 'tailwind-merge', 'class-variance-authority'],
                },
            },
        },
    },
});
