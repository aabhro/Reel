-- Reel account storage. Run this file once in the Supabase SQL Editor.
-- The browser uses only the publishable/anon key. RLS scopes each row to auth.uid().
-- Never expose a Supabase service_role key in the frontend.

create table if not exists public.reel_account_state (
  user_id uuid primary key references auth.users (id) on delete cascade,
  account jsonb not null default '{}'::jsonb,
  state jsonb not null default '{"profiles":[],"activeProfileId":null,"settings":{}}'::jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table public.reel_account_state enable row level security;
revoke all on table public.reel_account_state from anon, authenticated;
grant select, insert, update, delete on table public.reel_account_state to authenticated;

drop policy if exists "Users can read their own Reel account" on public.reel_account_state;
create policy "Users can read their own Reel account"
  on public.reel_account_state for select to authenticated
  using ((select auth.uid()) = user_id);

drop policy if exists "Users can create their own Reel account" on public.reel_account_state;
create policy "Users can create their own Reel account"
  on public.reel_account_state for insert to authenticated
  with check ((select auth.uid()) = user_id);

drop policy if exists "Users can update their own Reel account" on public.reel_account_state;
create policy "Users can update their own Reel account"
  on public.reel_account_state for update to authenticated
  using ((select auth.uid()) = user_id)
  with check ((select auth.uid()) = user_id);

drop policy if exists "Users can delete their own Reel account" on public.reel_account_state;
create policy "Users can delete their own Reel account"
  on public.reel_account_state for delete to authenticated
  using ((select auth.uid()) = user_id);

create index if not exists reel_account_state_updated_at_idx
  on public.reel_account_state (updated_at desc);
