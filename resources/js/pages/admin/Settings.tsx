import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Save, Settings as SettingsIcon } from 'lucide-react';
import api from '../../lib/axios';

export default function AdminSettings() {
  const [data, setData] = useState<Record<string, any[]>>({});
  const [edits, setEdits] = useState<Record<string, string>>({});

  useEffect(() => { api.get('/admin/settings').then((r) => setData(r.data)); }, []);

  const onChange = (key: string, value: string) => setEdits({ ...edits, [key]: value });

  const save = async () => {
    const settings = Object.entries(edits).map(([key, value]) => ({ key, value }));
    if (settings.length === 0) return;
    await api.put('/admin/settings', { settings });
    toast.success('Settings disimpan');
    setEdits({});
    api.get('/admin/settings').then((r) => setData(r.data));
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5 flex items-center justify-between">
        <h2 className="font-bold text-xl text-white flex items-center gap-2"><SettingsIcon className="h-5 w-5 text-brand-300" /> Pengaturan</h2>
        <button onClick={save} disabled={Object.keys(edits).length === 0} className="btn-primary !py-2 text-sm"><Save className="h-4 w-4 inline mr-1" /> Simpan</button>
      </div>

      {Object.entries(data).map(([group, items]) => (
        <div key={group} className="glass-card p-5">
          <h3 className="font-bold text-white capitalize mb-3">{group}</h3>
          <div className="space-y-3">
            {items.map((s) => (
              <div key={s.key}>
                <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 block">{s.label ?? s.key}</label>
                <input
                  defaultValue={s.value}
                  onChange={(e) => onChange(s.key, e.target.value)}
                  className="input"
                />
                {s.description && <p className="text-[10px] text-brand-300/60 mt-1">{s.description}</p>}
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}
