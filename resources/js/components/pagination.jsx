import React from 'react';
import { Link } from '@inertiajs/react';

export default function Pagination({ links }) {
    // Sembunyikan jika link kurang dari atau sama dengan 3 (biasanya prev, 1 page, next)
    if (!links || links.length <= 3) return null;

    // Fungsi untuk mengubah label bahasa Inggris ke Indonesia
    const processLabel = (label) => {
        if (label.includes('Previous')) return 'Sebelumnya';
        if (label.includes('Next')) return 'Selanjutnya';
        return label;
    };

    return (
        <div className="flex justify-center mt-12 gap-1 flex-wrap">
            {links.map((link, key) => {
                const label = processLabel(link.label);

                if (link.url === null) {
                    return (
                        <span
                            key={key}
                            className="px-4 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed opacity-50"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={key}
                        href={link.url}
                        preserveScroll
                        preserveState
                        className={`px-4 py-2 rounded-lg text-sm font-medium transition border ${
                            link.active
                                ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-blue-600'
                        }`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}