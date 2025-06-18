<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My OLX Subscriptions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Subscription</h3>

                    <form method="POST" action="{{ route('subscriptions.store') }}" class="mb-8">
                        @csrf
                        <div class="flex items-center space-x-4">
                            <div class="flex-grow">
                                <x-input-label for="url" :value="__('OLX Ad URL')" />
                                <x-text-input id="url" class="block mt-1 w-full" type="url" name="url" :value="old('url')" required autofocus placeholder="e.g., https://www.olx.ua/d/uk/obyavlenie/nazva-ogoloshennya-ID.html" />
                                <x-input-error :messages="$errors->get('url')" class="mt-2" />
                            </div>
                            <x-primary-button class="mt-7">
                                {{ __('Add Subscription') }}
                            </x-primary-button>
                        </div>
                    </form>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Success!</strong>
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <h3 class="text-lg font-medium text-gray-900 mb-4">Your Current Subscriptions</h3>

                    @if ($subscriptions->isEmpty())
                        <p class="text-gray-600">You don't have any subscriptions yet. Add one above!</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        OLX Ad
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current Price
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Last Checked
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($subscriptions as $subscription)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ $subscription->olxAd->url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                                {{ Str::limit($subscription->olxAd->url, 70) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($subscription->olxAd->current_price)
                                                {{ number_format($subscription->olxAd->current_price, 2) }} {{ $subscription->olxAd->currency }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $subscription->olxAd->last_checked_at ? $subscription->olxAd->last_checked_at->diffForHumans() : 'Never' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="{{ route('subscriptions.destroy', $subscription) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this subscription?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
