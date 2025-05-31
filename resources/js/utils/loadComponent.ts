export const loadComponent = async (loader: () => Promise<any>) => {
  const mod = await loader();
  return mod.default ?? mod;
};
