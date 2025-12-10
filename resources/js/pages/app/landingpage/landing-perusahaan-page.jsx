import React, { useState } from 'react';
import { router } from '@inertiajs/react'; // Hapus 'Link' dari sini
import LandingLayout from '@/layouts/landing-layout';
import Pagination from '@/components/pagination'; 
import { PerusahaanDetailDialog } from './dialogs/perusahaan-detail-dialog';

// Helper Icon
const EyeIcon = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className={className}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>
);

export default function LandingPerusahaanPage({ auth, contentData, state }) {
    const [searchTerm, setSearchTerm] = useState(state.search || '');
    const [isCompanyOpen, setIsCompanyOpen] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState(null);

    const handleSearch = () => {
        router.get(route('landing.perusahaan'), { search: searchTerm }, { preserveState: true, preserveScroll: true });
    };

    const handleShowCompany = (item) => {
        setSelectedCompany(item);
        setIsCompanyOpen(true);
    };

    return (
        <LandingLayout auth={auth} activeMenu="perusahaan" title="Daftar Perusahaan - Career Center IT Del">
            
            {/* Header / Search Section */}
            <div className="bg-blue-50 py-10 mb-8 border-b border-blue-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-bold text-slate-800 mb-6 text-center md:text-left">Daftar Perusahaan</h2>
                    
                    <div className="bg-white rounded-lg p-4 shadow-sm max-w-2xl">
                        <div className="flex flex-col md:flex-row gap-3">
                            <input 
                                type="text" 
                                placeholder="Cari nama perusahaan..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="flex-1 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            />
                            <button 
                                onClick={handleSearch}
                                className="bg-[#5c8bf5] text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-600 transition shadow-sm"
                            >
                                Cari
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* List Content */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-20">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {contentData.data.length > 0 ? (
                        contentData.data.map((item) => (
                            <div key={item.id_perusahaan} className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm hover:shadow-md transition flex flex-col gap-4 h-full">
                                <div className="flex items-center gap-4">
                                    <div className="w-16 h-16 flex-shrink-0 flex items-center justify-center border rounded-lg bg-gray-50 p-1">
                                        <img 
                                            src={item.url_logo || '/images/default-company.png'} 
                                            alt={item.nama} 
                                            className="max-w-full max-h-full object-contain"
                                            onError={(e) => e.target.src = 'https://ui-avatars.com/api/?name=' + item.nama}
                                        />
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-bold text-[#1F2937] line-clamp-2">{item.nama}</h3>
                                        <p className="text-sm text-gray-500 line-clamp-1">{item.lokasi || 'Lokasi tidak tersedia'}</p>
                                    </div>
                                </div>
                                
                                <div className="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                                    <span className="bg-[#FFF4CC] text-[#8A6D3B] text-xs px-3 py-1 rounded-full font-medium">
                                        {item.total_jobs} Lowongan Aktif
                                    </span>
                                    
                                    {/* --- TOMBOL DETAIL (UPDATED STYLE) --- */}
                                    <button 
                                        onClick={() => handleShowCompany(item)}
                                        className="text-blue-600 border border-blue-600 px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-50 transition flex items-center gap-2"
                                    >
                                        <EyeIcon className="w-4 h-4" /> Detail
                                    </button>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="col-span-full text-center py-20 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <p className="text-gray-500 text-lg">Tidak ada perusahaan yang ditemukan.</p>
                        </div>
                    )}
                </div>

                {/* --- PAGINATION --- */}
                <Pagination links={contentData.links} />
            </div>

            <PerusahaanDetailDialog 
                openDialog={isCompanyOpen} 
                setOpenDialog={setIsCompanyOpen} 
                dataDetail={selectedCompany} 
            />
        </LandingLayout>
    );
}