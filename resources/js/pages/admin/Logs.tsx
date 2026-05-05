import { useEffect, useState } from 'react';
import { FileClock, User, Wallet, ClockIcon } from 'lucide-react';
import api from '../../lib/axios';
import { formatDateTime } from '../../lib/utils';

type Tab = 'users' | 'attendance' | 'cash-flow';

export default function AdminLogs() {
  const [tab, setTab] = useState<Tab>('users');
  const [data, setData] = useState<any>({ data: [] });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    api.get(`/admin/logs/${tab}`).then((r) => setData(r.data)).finally(() => setLoading(false));
  }, [tab]);

  return (
    <div className="space-y-4">
      <div className="glass-card p-5">
        <h2 className="font-bold text-xl text-white flex items-center gap-2"><FileClock className="h-5 w-5 text-brand-300" /> Activity Logs</h2>
        <p className="text-xs text-brand-200/60 mt-1">Audit trail untuk semua aktivitas penting</p>
      </div>

      <div className="grid grid-cols-3 gap-2">
        <Tab tab="users" current={tab} onClick={() => setTab('users')} icon={<User className="h-4 w-4" />} label="User" />
        <Tab tab="attendance" current={tab} onClick={() => setTab('attendance')} icon={<ClockIcon className="h-4 w-4" />} label="Absensi" />
        <Tab tab="cash-flow" current={tab} onClick={() => setTab('cash-flow')} icon={<Wallet className="h-4 w-4" />} label="Kas" />
      </div>

      <div className="glass-card p-1">
        {loading ? (
          <div className="space-y-2 p-3">{[1, 2, 3, 4, 5].map((i) => <div key={i} className="h-14 skeleton rounded-xl" />)}</div>
        ) : (
          <div className="divide-y divide-brand-400/10">
            {(data.data ?? []).map((l: any) => (
              <div key={l.id} className="p-3 t-row">
                <div className="flex items-center justify-between gap-3">
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="badge text-[9px]">{l.action}</span>
                      {l.field && <span className="text-xs text-brand-300">@{l.field}</span>}
                      <span className="text-[10px] text-brand-200/50">{formatDateTime(l.logged_at)}</span>
                    </div>
                    <p className="text-sm text-white mt-1">
                      {tab === 'users' && (
                        <>
                          <b>{l.user?.name ?? `User #${l.user_id}`}</b>
                          {l.actor && l.actor.id !== l.user_id && <span className="text-brand-200/70"> by {l.actor.name}</span>}
                        </>
                      )}
                      {tab === 'attendance' && (
                        <><b>{l.user?.name ?? `User #${l.user_id}`}</b> {l.face_verified && <span className="badge badge-success text-[9px]">Face ✓</span>}</>
                      )}
                      {tab === 'cash-flow' && (
                        <>
                          <b>{l.actor?.name}</b> {l.cash_flow ? <>→ {l.cash_flow.description} ({l.amount})</> : null}
                        </>
                      )}
                    </p>
                    {(l.before || l.after) && (
                      <div className="text-[10px] text-brand-200/60 mt-1 font-mono">
                        {l.before && <div>↪ {JSON.stringify(l.before).substring(0, 80)}</div>}
                        {l.after && <div>✓ {JSON.stringify(l.after).substring(0, 80)}</div>}
                      </div>
                    )}
                  </div>
                  {l.ip_address && <span className="text-[10px] text-brand-300/40 font-mono">{l.ip_address}</span>}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function Tab({ tab, current, onClick, icon, label }: any) {
  return (
    <button onClick={onClick} className={`p-3 rounded-xl border-2 transition flex items-center justify-center gap-2 text-sm font-semibold ${
      tab === current ? 'border-brand-400 bg-brand-500/15 text-brand-100' : 'border-brand-400/15 bg-surface-200/30 text-brand-300/70'
    }`}>
      {icon} {label}
    </button>
  );
}
